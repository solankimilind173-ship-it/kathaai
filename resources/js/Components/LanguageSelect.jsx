import Select from 'react-select';

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

    const selectedValue = isMulti
        ? options.filter(option => value?.includes(option.value))
        : options.find(option => option.value === value) || null;

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
