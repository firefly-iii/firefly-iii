@if($convertToNative)
    <template x-if="convertToNative">
    <span>
    <template x-if="{{ $native }}_raw < 0">
        <span class="text-danger">
            <span x-text="{{ $native }}"></span>
        </span>
    </template>
    <template x-if="{{ $native }}_raw >= 0">
        <template x-if="'transfer' === {{ $type }}">
            <span class="text-primary">
                <span x-text="{{ $native }}"></span>
            </span>
        </template>
        <template x-if="'transfer' !== {{ $type }}">
            <span class="text-success">
                <span x-text="{{ $native }}"></span>
            </span>
        </template>
    </template>
        </span>
    </template>
    <template x-if="!convertToNative">
    <span>
    <template x-if="{{ $amount }}_raw < 0">

        <span>
        <template x-if="'transfer' === {{ $type }}">
            <span class="text-primary">
                <span x-text="{{ $native }}"></span>
            </span>
        </template>
        <template x-if="'transfer' !== {{ $type }}">
            <span class="text-danger">
                <span x-text="{{ $native }}"></span>
            </span>
        </template>
            </span>


    </template>
    <template x-if="{{ $amount }}_raw >= 0">
        <span>
        <template x-if="'transfer' === {{ $type }}">
            <span class="text-primary">
                <span x-text="{{ $native }}"></span>
            </span>
        </template>
        <template x-if="'transfer' !== {{ $type }}">
            <span class="text-success">
                <span x-text="{{ $native }}"></span>
            </span>
        </template>
            </span>
    </template>
        </span>
    </template>
@else
    <template x-if="{{ $amount }}_raw < 0">
        <span class="text-danger">
            <span x-text="{{ $amount }}"></span>
        </span>
    </template>
    <template x-if="{{ $amount }}_raw >= 0">
        <template x-if="'transfer' === {{ $type }}">
            <span class="text-primary">
                <span x-text="{{ $amount }}"></span>
            </span>
        </template>
        <template x-if="'transfer' !== {{ $type }}">
            <span class="text-success">
                <span x-text="{{ $amount }}"></span>
            </span>
        </template>
    </template>
@endif
