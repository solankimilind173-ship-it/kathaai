import Select from 'react-select';

function sameId(a, b) {
    if (a == null || b == null) return false;
    return String(a) === String(b);
}

export default function LanguageSelect({
    languages = [],
    value = null,
    onChange,
    isMulti = false,
    placeholder = "Select language"
}) {

    const options = languages.map(lang => ({
        value: lang.id,
        label: `${lang.name} (${lang.code})`
    }));

    // Normalize: single-select can receive a single id or [id]; compare by string to avoid number/string mismatch
    const rawSingle = Array.isArray(value) ? value[0] : value;
    const selectedValue = isMulti
        ? options.filter(option => Array.isArray(value) && value.some(v => sameId(v, option.value)))
        : (options.find(option => sameId(option.value, rawSingle)) || null);

    return (
        <Select
            options={options}
            value={selectedValue}
            onChange={(selected) => {
                if (isMulti) {
                    onChange(selected ? selected.map(item => item.value) : []);
                } else {
                    onChange(selected ? selected.value : null);
                }
            }}
            isMulti={isMulti}
            placeholder={placeholder}
            className="react-select-container"
            classNamePrefix="react-select"
        />
    );
}
