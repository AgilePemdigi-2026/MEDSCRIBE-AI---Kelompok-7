{{--
    Toolbar pencarian dan filter untuk daftar admin.
    Parameter: route, search, placeholder, active (bool),
               select (opsional) = ['name' => ..., 'label' => ..., 'options' => [nilai => label], 'selected' => ...]
--}}
<form method="GET" action="{{ route($route) }}" class="grid grid-cols-1 sm:flex sm:flex-wrap items-end gap-3" role="search">
    <div class="sm:w-72">
        <label for="cari-{{ $route }}" class="field-label">Cari</label>
        <div class="field-icon">
            <x-icon name="search" aria-hidden="true" />
            <input type="search" id="cari-{{ $route }}" name="cari" value="{{ $search }}" placeholder="{{ $placeholder }}" class="field">
        </div>
    </div>

    @isset($select)
        <div class="sm:w-52">
            <label for="filter-{{ $route }}" class="field-label">{{ $select['label'] }}</label>
            <select id="filter-{{ $route }}" name="{{ $select['name'] }}" class="field" onchange="this.form.submit()">
                @foreach ($select['options'] as $value => $text)
                    <option value="{{ $value }}" @selected((string) $select['selected'] === (string) $value)>{{ $text }}</option>
                @endforeach
            </select>
        </div>
    @endisset

    <div class="flex gap-2">
        <button type="submit" class="btn btn-navy">Cari</button>
        @if ($active)
            <a href="{{ route($route) }}" class="btn btn-soft">Hapus filter</a>
        @endif
    </div>
</form>
