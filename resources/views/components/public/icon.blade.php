@props(['name', 'class' => 'size-5'])
@php($paths = [
    'search' => '<circle cx="11" cy="11" r="7"/><path d="m20 20-3.5-3.5"/>',
    'book' => '<path d="M4 5.5A2.5 2.5 0 0 1 6.5 3H11v16H6.5A2.5 2.5 0 0 0 4 21.5zM20 5.5A2.5 2.5 0 0 0 17.5 3H13v16h4.5a2.5 2.5 0 0 1 2.5 2.5z"/>',
    'arrow' => '<path d="M5 12h14M14 7l5 5-5 5"/>',
    'grid' => '<rect x="4" y="4" width="6" height="6"/><rect x="14" y="4" width="6" height="6"/><rect x="4" y="14" width="6" height="6"/><rect x="14" y="14" width="6" height="6"/>',
    'list' => '<path d="M9 6h11M9 12h11M9 18h11M4 6h.01M4 12h.01M4 18h.01"/>',
    'shelf' => '<path d="M4 19V6h4v13M10 19V4h4v15M16 19V8h4v11M3 21h18"/>',
    'lock' => '<rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/>',
    'filter' => '<path d="M4 5h16l-6 7v5l-4 2v-7z"/>',
    'chevron' => '<path d="m9 18 6-6-6-6"/>',
    'announcement' => '<path d="M4 13V9l13-4v12L4 13zM8 14v5H5l-1-6M17 9a4 4 0 0 1 0 4"/>',
])
<svg {{ $attributes->merge(['class' => $class, 'viewBox' => '0 0 24 24', 'fill' => 'none', 'stroke' => 'currentColor', 'stroke-width' => '1.75', 'stroke-linecap' => 'round', 'stroke-linejoin' => 'round', 'aria-hidden' => 'true']) }}>{!! $paths[$name] ?? '' !!}</svg>
