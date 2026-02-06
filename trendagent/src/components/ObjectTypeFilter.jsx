import './ObjectTypeFilter.css'

const ObjectTypeFilter = ({ selectedType, onChange }) => {
  const objectTypes = [
    { value: 'apartments', label: 'Квартиры', icon: '🏠' },
    { value: 'parkings', label: 'Паркинги', icon: '🚗' },
    { value: 'houses', label: 'Дома', icon: '🏡' },
    { value: 'plots', label: 'Участки', icon: '🌳' },
    { value: 'commercial', label: 'Коммерция', icon: '🏢' },
  ]

  return (
    <div className="object-type-filter">
      <div className="filter-tabs">
        {objectTypes.map((type) => (
          <button
            key={type.value}
            className={`filter-tab ${selectedType === type.value ? 'active' : ''}`}
            onClick={() => onChange(type.value)}
          >
            <span className="filter-icon">{type.icon}</span>
            <span>{type.label}</span>
          </button>
        ))}
      </div>
    </div>
  )
}

export default ObjectTypeFilter
