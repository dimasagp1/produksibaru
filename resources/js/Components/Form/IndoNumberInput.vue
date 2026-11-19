<script setup>
import { ref, watch, nextTick } from 'vue';

const props = defineProps({
    modelValue: {
        type: [Number, String],
        default: null,
    },
    allowDecimal: {
        type: Boolean,
        default: false,
    },
    placeholder: {
        type: String,
        default: '0',
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    min: {
        type: Number,
        default: 0,
    },
    max: {
        type: Number,
        default: undefined,
    },
    inputClass: {
        type: [String, Array, Object],
        default: '',
    },
});

const emit = defineEmits(['update:modelValue', 'blur', 'focus', 'change']);

const inputRef = ref(null);
const displayValue = ref('');

/**
 * Format dari modelValue (misal: 6000, "7000.00", 0) ke format tampilan Indonesia
 */
function formatFromModel(val, allowDecimal = false) {
    if (val === null || val === undefined || val === '') return '';
    if (!allowDecimal) {
        const num = Number(val);
        if (!isNaN(num)) {
            return Math.round(num).toLocaleString('id-ID');
        }
        const digits = String(val).replace(/[^\d]/g, '');
        const normalized = digits.length > 1 ? digits.replace(/^0+(?=\d)/, '') : digits;
        return normalized.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    // Jika desimal
    const num = Number(val);
    if (!isNaN(num)) {
        return num.toLocaleString('id-ID', { maximumFractionDigits: 2 });
    }
    return String(val);
}

/**
 * Format dari hasil ketikan user (misal user mengetik "6000" atau "6.0000")
 */
function formatFromTyping(rawInput, allowDecimal = false) {
    if (rawInput === null || rawInput === undefined || rawInput === '') return '';
    let str = String(rawInput);

    if (!allowDecimal) {
        const digits = str.replace(/[^\d]/g, '');
        if (!digits) return '';
        const normalized = digits.length > 1 ? digits.replace(/^0+(?=\d)/, '') : digits;
        return normalized.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    // Jika desimal diizinkan
    const parts = str.split(',');
    let intDigits = parts[0].replace(/[^\d]/g, '');
    intDigits = intDigits.length > 1 ? intDigits.replace(/^0+(?=\d)/, '') : (intDigits || '0');
    const formattedInt = intDigits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');

    if (parts.length > 1) {
        const decDigits = parts[1].replace(/[^\d]/g, '');
        return `${formattedInt},${decDigits}`;
    }
    return formattedInt;
}

/**
 * Parse string format Indonesia ("6.000") kembali ke Number (6000)
 */
function parseIndo(val, allowDecimal = false) {
    if (val === null || val === undefined || val === '') return 0;
    const str = String(val);

    if (!allowDecimal) {
        const digits = str.replace(/[^\d]/g, '');
        return digits ? parseInt(digits, 10) : 0;
    }

    const clean = str.replace(/\./g, '').replace(/,/g, '.').replace(/[^\d.]/g, '');
    const num = parseFloat(clean);
    return isNaN(num) ? 0 : num;
}

// Watch modelValue dari parent (props)
watch(
    () => props.modelValue,
    (newVal) => {
        const currentNum = parseIndo(displayValue.value, props.allowDecimal);
        const newNum = newVal === null || newVal === undefined || newVal === '' ? null : Number(newVal);

        if (newNum === null) {
            displayValue.value = '';
        } else if (newNum !== currentNum || (newNum === 0 && displayValue.value === '')) {
            displayValue.value = formatFromModel(newVal, props.allowDecimal);
        }
    },
    { immediate: true }
);

function handleInput(event) {
    const el = event.target;
    const oldVal = el.value;
    const cursor = el.selectionStart || 0;

    // Hitung karakter valid sebelum kursor
    const validCharRegex = props.allowDecimal ? /[\d,]/ : /[\d]/;
    let countBefore = 0;
    for (let i = 0; i < cursor; i++) {
        if (validCharRegex.test(oldVal[i])) {
            countBefore++;
        }
    }

    // Format ulang dari ketikan user
    const formatted = formatFromTyping(oldVal, props.allowDecimal);
    displayValue.value = formatted;

    // Nilai numerik untuk dikirim ke parent via v-model
    const numericValue = parseIndo(formatted, props.allowDecimal);
    emit('update:modelValue', numericValue);

    // Pulihkan posisi kursor dengan presisi
    nextTick(() => {
        if (!el) return;
        let newCursor = 0;
        let countAfter = 0;
        for (let i = 0; i < formatted.length; i++) {
            if (validCharRegex.test(formatted[i])) {
                countAfter++;
            }
            if (countAfter === countBefore) {
                newCursor = i + 1;
                break;
            }
        }
        if (countAfter < countBefore) {
            newCursor = formatted.length;
        }
        el.setSelectionRange(newCursor, newCursor);
    });
}

function handleKeyDown(event) {
    // Jika tombol Backspace ditekan tepat di belakang titik pemisah ribuan
    if (event.key === 'Backspace') {
        const el = event.target;
        const cursor = el.selectionStart;
        if (cursor === el.selectionEnd && cursor > 0 && el.value[cursor - 1] === '.') {
            event.preventDefault();
            const val = el.value;
            // Hapus angka sebelum titik tersebut
            const newVal = val.slice(0, cursor - 2) + val.slice(cursor - 1);
            const formatted = formatFromTyping(newVal, props.allowDecimal);
            displayValue.value = formatted;
            const numericValue = parseIndo(formatted, props.allowDecimal);
            emit('update:modelValue', numericValue);
            nextTick(() => {
                const newPos = Math.max(0, cursor - 2);
                el.setSelectionRange(newPos, newPos);
            });
        }
    }
}

function handleBlur(event) {
    if (props.modelValue !== null && props.modelValue !== undefined) {
        displayValue.value = formatFromModel(props.modelValue, props.allowDecimal);
    }
    emit('blur', event);
}

function handleFocus(event) {
    emit('focus', event);
}
</script>

<template>
    <input
        ref="inputRef"
        type="text"
        inputmode="numeric"
        autocomplete="off"
        :value="displayValue"
        :placeholder="placeholder"
        :disabled="disabled"
        :class="inputClass"
        @input="handleInput"
        @keydown="handleKeyDown"
        @blur="handleBlur"
        @focus="handleFocus"
    />
</template>
