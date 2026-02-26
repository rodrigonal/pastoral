@props([
    'model' => 'valor',
    'placeholder' => 'R$ 0,00',
])

<div
    x-data="{
        formatCurrency(digits) {
            if (!digits && digits !== 0) return '';
            const str = String(digits).replace(/\D/g, '');
            if (str === '') return '';
            const padded = str.padStart(3, '0');
            const cents = padded.slice(-2);
            const intPart = padded.slice(0, -2).replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            return 'R$ ' + intPart + ',' + cents;
        },
        parseToDigits(val) {
            if (!val) return '';
            const noDots = String(val).replace(/\\./g, '');
            const normalized = noDots.replace(',', '.');
            const num = parseFloat(normalized || 0);
            return String(Math.round(num * 100));
        }
    }"
    x-init="
        $nextTick(() => {
            const val = $wire.get('{{ $model }}');
            if (val) {
                const digits = parseToDigits(val);
                $refs.input.value = formatCurrency(digits);
            }
        });
    "
>
    <input
        type="text"
        x-ref="input"
        {{ $attributes->merge(['class' => 'w-full rounded border border-zinc-300 px-3 py-2 dark:border-zinc-600 dark:bg-zinc-700']) }}
        placeholder="{{ $placeholder }}"
        inputmode="numeric"
        autocomplete="off"
        x-on:input="
            const digits = $event.target.value.replace(/\D/g, '');
            const formatted = formatCurrency(digits);
            $event.target.value = formatted;
            $wire.set('{{ $model }}', formatted);
        "
    />
</div>
