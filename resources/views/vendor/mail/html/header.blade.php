@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
    <h2>{{ $slot }}</h2>
</a>
</td>
</tr>
