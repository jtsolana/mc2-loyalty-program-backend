import { useEffect, useRef, useState } from 'react';
import { CalendarIcon, ChevronLeftIcon, ChevronRightIcon } from 'lucide-react';
import { cn } from '@/lib/utils';

export interface DateRange {
    start: string;
    end: string;
}

interface DateRangePickerProps {
    value: DateRange;
    onChange: (range: DateRange) => void;
    onApply: (range: DateRange) => void;
}

const PRESETS = [
    { label: 'Today', key: 'today' },
    { label: 'Yesterday', key: 'yesterday' },
    { label: 'Last 7 Days', key: 'last7' },
    { label: 'Last 30 Days', key: 'last30' },
    { label: 'This Month', key: 'thisMonth' },
    { label: 'Last Month', key: 'lastMonth' },
    { label: 'Custom Range', key: 'custom' },
] as const;

type PresetKey = (typeof PRESETS)[number]['key'];

function toDateStr(d: Date): string {
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
}

function parseDate(str: string): Date {
    const [y, m, d] = str.split('-').map(Number);
    return new Date(y, m - 1, d);
}

function formatDisplay(start: string, end: string): string {
    return `${start} - ${end}`;
}

function resolvePreset(key: PresetKey): DateRange {
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    switch (key) {
        case 'today':
            return { start: toDateStr(today), end: toDateStr(today) };
        case 'yesterday': {
            const y = new Date(today);
            y.setDate(y.getDate() - 1);
            return { start: toDateStr(y), end: toDateStr(y) };
        }
        case 'last7': {
            const s = new Date(today);
            s.setDate(s.getDate() - 6);
            return { start: toDateStr(s), end: toDateStr(today) };
        }
        case 'last30': {
            const s = new Date(today);
            s.setDate(s.getDate() - 29);
            return { start: toDateStr(s), end: toDateStr(today) };
        }
        case 'thisMonth': {
            const s = new Date(today.getFullYear(), today.getMonth(), 1);
            const e = new Date(today.getFullYear(), today.getMonth() + 1, 0);
            return { start: toDateStr(s), end: toDateStr(e) };
        }
        case 'lastMonth': {
            const s = new Date(today.getFullYear(), today.getMonth() - 1, 1);
            const e = new Date(today.getFullYear(), today.getMonth(), 0);
            return { start: toDateStr(s), end: toDateStr(e) };
        }
        default:
            return { start: toDateStr(today), end: toDateStr(today) };
    }
}

function detectPreset(range: DateRange): PresetKey {
    for (const preset of PRESETS) {
        if (preset.key === 'custom') continue;
        const resolved = resolvePreset(preset.key);
        if (resolved.start === range.start && resolved.end === range.end) {
            return preset.key;
        }
    }
    return 'custom';
}

function daysInMonth(year: number, month: number): number {
    return new Date(year, month + 1, 0).getDate();
}

function firstDayOfMonth(year: number, month: number): number {
    return new Date(year, month, 1).getDay();
}

const MONTH_NAMES = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
const DAY_NAMES = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];

interface CalendarMonthProps {
    year: number;
    month: number;
    rangeStart: string | null;
    rangeEnd: string | null;
    hoverDate: string | null;
    onDayClick: (dateStr: string) => void;
    onDayHover: (dateStr: string | null) => void;
}

function CalendarMonth({ year, month, rangeStart, rangeEnd, hoverDate, onDayClick, onDayHover }: CalendarMonthProps) {
    const days = daysInMonth(year, month);
    const firstDay = firstDayOfMonth(year, month);

    const effectiveEnd = rangeStart && !rangeEnd && hoverDate ? hoverDate : rangeEnd;

    function dayClass(dateStr: string): string {
        const isStart = dateStr === rangeStart;
        const isEnd = dateStr === effectiveEnd;
        const inRange =
            rangeStart &&
            effectiveEnd &&
            dateStr > (rangeStart < effectiveEnd ? rangeStart : effectiveEnd) &&
            dateStr < (rangeStart < effectiveEnd ? effectiveEnd : rangeStart);

        const classes: string[] = ['flex items-center justify-center text-sm h-8 w-8 rounded-full cursor-pointer select-none'];

        if (isStart || isEnd) {
            classes.push('bg-blue-500 text-white font-semibold');
        } else if (inRange) {
            classes.push('bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-200 rounded-none');
        } else {
            classes.push('hover:bg-accent text-foreground');
        }

        return classes.join(' ');
    }

    const cells: (number | null)[] = [...Array(firstDay).fill(null), ...Array.from({ length: days }, (_, i) => i + 1)];

    return (
        <div className="w-56">
            <div className="mb-2 text-center text-sm font-semibold text-foreground">
                {MONTH_NAMES[month]} {year}
            </div>
            <div className="grid grid-cols-7 gap-y-0.5">
                {DAY_NAMES.map((d) => (
                    <div key={d} className="flex h-8 w-8 items-center justify-center text-xs font-medium text-muted-foreground">
                        {d}
                    </div>
                ))}
                {cells.map((day, i) => {
                    if (!day) {
                        return <div key={`empty-${i}`} className="h-8 w-8" />;
                    }
                    const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                    return (
                        <div
                            key={dateStr}
                            className={dayClass(dateStr)}
                            onClick={() => onDayClick(dateStr)}
                            onMouseEnter={() => onDayHover(dateStr)}
                            onMouseLeave={() => onDayHover(null)}
                        >
                            {day}
                        </div>
                    );
                })}
            </div>
        </div>
    );
}

export function DateRangePicker({ value, onChange, onApply }: DateRangePickerProps) {
    const [open, setOpen] = useState(false);
    const [activePreset, setActivePreset] = useState<PresetKey>(() => detectPreset(value));
    const [tempRange, setTempRange] = useState<{ start: string | null; end: string | null }>({
        start: value.start,
        end: value.end,
    });
    const [hoverDate, setHoverDate] = useState<string | null>(null);
    const containerRef = useRef<HTMLDivElement>(null);

    // Left calendar shows the month before the end date's month
    const [leftYear, setLeftYear] = useState<number>(() => {
        const d = parseDate(value.end);
        const prev = new Date(d.getFullYear(), d.getMonth() - 1, 1);
        return prev.getFullYear();
    });
    const [leftMonth, setLeftMonth] = useState<number>(() => {
        const d = parseDate(value.end);
        const prev = new Date(d.getFullYear(), d.getMonth() - 1, 1);
        return prev.getMonth();
    });

    const rightYear = leftMonth === 11 ? leftYear + 1 : leftYear;
    const rightMonth = (leftMonth + 1) % 12;

    useEffect(() => {
        function handleClickOutside(e: MouseEvent) {
            if (containerRef.current && !containerRef.current.contains(e.target as Node)) {
                setOpen(false);
            }
        }
        if (open) {
            document.addEventListener('mousedown', handleClickOutside);
        }
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, [open]);

    function handlePresetClick(key: PresetKey) {
        setActivePreset(key);
        if (key !== 'custom') {
            const resolved = resolvePreset(key);
            setTempRange({ start: resolved.start, end: resolved.end });
            onChange(resolved);
        }
    }

    function handleDayClick(dateStr: string) {
        if (!tempRange.start || (tempRange.start && tempRange.end)) {
            setTempRange({ start: dateStr, end: null });
            setActivePreset('custom');
        } else {
            const start = tempRange.start;
            const ordered = dateStr < start ? { start: dateStr, end: start } : { start, end: dateStr };
            setTempRange(ordered);
            onChange(ordered);
            setActivePreset(detectPreset(ordered));
        }
    }

    function handleApply() {
        if (tempRange.start && tempRange.end) {
            onApply({ start: tempRange.start, end: tempRange.end });
            setOpen(false);
        }
    }

    function navigateLeft() {
        if (leftMonth === 0) {
            setLeftMonth(11);
            setLeftYear((y) => y - 1);
        } else {
            setLeftMonth((m) => m - 1);
        }
    }

    function navigateRight() {
        if (leftMonth === 11) {
            setLeftMonth(0);
            setLeftYear((y) => y + 1);
        } else {
            setLeftMonth((m) => m + 1);
        }
    }

    const displayStart = tempRange.start ?? value.start;
    const displayEnd = tempRange.end ?? value.end;

    return (
        <div ref={containerRef} className="relative">
            <div
                className="flex cursor-pointer items-center overflow-hidden rounded-md border border-input bg-background shadow-xs"
                onClick={() => setOpen((o) => !o)}
            >
                <div className="flex items-center gap-2 border-r border-input px-3 py-2 text-muted-foreground">
                    <CalendarIcon className="size-4" />
                </div>
                <div className="px-3 py-2 text-sm text-foreground">
                    {formatDisplay(displayStart, displayEnd)}
                </div>
                <button
                    type="button"
                    className="bg-primary px-3 py-2 text-sm font-semibold text-primary-foreground hover:bg-primary/90 cursor-pointer"
                    onClick={(e) => {
                        e.stopPropagation();
                        handleApply();
                    }}
                >
                    Go!
                </button>
            </div>

            {open && (
                <div className="absolute right-0 top-full z-50 mt-1 flex overflow-hidden rounded-xl border border-border bg-card shadow-lg">
                    {/* Presets */}
                    <div className="flex min-w-36 flex-col border-r border-border py-1">
                        {PRESETS.map((preset) => (
                            <button
                                key={preset.key}
                                type="button"
                                className={cn(
                                    'px-4 py-2 text-left text-sm transition-colors hover:bg-accent',
                                    activePreset === preset.key ? 'bg-primary text-primary-foreground hover:bg-primary/90' : 'text-foreground',
                                )}
                                onClick={() => handlePresetClick(preset.key)}
                            >
                                {preset.label}
                            </button>
                        ))}
                    </div>

                    {/* Calendars */}
                    <div className="flex flex-col gap-3 p-4">
                        <div className="flex items-center gap-4">
                            <button
                                type="button"
                                className="rounded p-1 hover:bg-accent"
                                onClick={navigateLeft}
                            >
                                <ChevronLeftIcon className="size-4" />
                            </button>
                            <div className="flex gap-6">
                                <CalendarMonth
                                    year={leftYear}
                                    month={leftMonth}
                                    rangeStart={tempRange.start}
                                    rangeEnd={tempRange.end}
                                    hoverDate={hoverDate}
                                    onDayClick={handleDayClick}
                                    onDayHover={setHoverDate}
                                />
                                <CalendarMonth
                                    year={rightYear}
                                    month={rightMonth}
                                    rangeStart={tempRange.start}
                                    rangeEnd={tempRange.end}
                                    hoverDate={hoverDate}
                                    onDayClick={handleDayClick}
                                    onDayHover={setHoverDate}
                                />
                            </div>
                            <button
                                type="button"
                                className="rounded p-1 hover:bg-accent"
                                onClick={navigateRight}
                            >
                                <ChevronRightIcon className="size-4" />
                            </button>
                        </div>
                    </div>
                </div>
            )}
        </div>
    );
}
