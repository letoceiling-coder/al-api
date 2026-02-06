import { useState, useEffect } from 'react'
import trendAgentAPI from '../services/api'
import './SearchFilters.css'

const SearchFilters = ({ objectType, filters, onFilterChange, onSearch }) => {
  const [localFilters, setLocalFilters] = useState(filters)
  const [cities, setCities] = useState([])
  const [showAdvanced, setShowAdvanced] = useState(false)
  const [loadingCities, setLoadingCities] = useState(false)

  useEffect(() => {
    loadCities()
  }, [])

  useEffect(() => {
    setLocalFilters(filters)
  }, [filters])

  const loadCities = async () => {
    setLoadingCities(true)
    try {
      const response = await trendAgentAPI.getCities()
      const citiesData = response.data || response
      setCities(Array.isArray(citiesData) ? citiesData : [])
    } catch (error) {
      console.error('Ошибка загрузки городов:', error)
    } finally {
      setLoadingCities(false)
    }
  }

  const handleFilterChange = (field, value) => {
    const newFilters = { ...localFilters, [field]: value }
    setLocalFilters(newFilters)
    onFilterChange(newFilters)
  }

  const handleSearch = () => {
    onSearch()
  }

  const handleReset = () => {
    const resetFilters = {
      city: '58c665588b6aa52311afa01b',
      phone: filters.phone,
      password: filters.password,
    }
    setLocalFilters(resetFilters)
    onFilterChange(resetFilters)
  }

  return (
    <div className="search-filters card">
      <div className="filters-header">
        <h2 className="filters-title">Фильтры поиска</h2>
        <button
          className="btn-toggle-advanced"
          onClick={() => setShowAdvanced(!showAdvanced)}
        >
          {showAdvanced ? 'Скрыть фильтры' : 'Все фильтры'}
        </button>
      </div>

      <div className="filters-content">
        {/* Поиск по тексту */}
        <div className="filter-group">
          <label className="filter-label">Поиск</label>
          <div className="search-input-wrapper">
            <input
              type="text"
              className="input search-input"
              placeholder="Метро, район, локация, ЖК, улица, застройщик"
              value={localFilters.text || ''}
              onChange={(e) => handleFilterChange('text', e.target.value)}
            />
            <svg className="search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
            </svg>
          </div>
        </div>

        {/* Основные фильтры */}
        <div className="filters-grid">
          {/* Город */}
          <div className="filter-group">
            <label className="filter-label">Город</label>
            <select
              className="select"
              value={localFilters.city || ''}
              onChange={(e) => handleFilterChange('city', e.target.value)}
            >
              {loadingCities ? (
                <option>Загрузка...</option>
              ) : (
                cities.map((city) => (
                  <option key={city.id} value={city.id}>
                    {city.name}
                  </option>
                ))
              )}
            </select>
          </div>

          {/* Фильтры для квартир */}
          {objectType === 'apartments' && (
            <>
              <div className="filter-group">
                <label className="filter-label">Тип квартиры</label>
                <RoomTypeSelector
                  value={localFilters.room || []}
                  onChange={(rooms) => handleFilterChange('room', rooms)}
                />
              </div>
              <div className="filter-group">
                <label className="filter-label">Цена от-до, ₽</label>
                <div className="price-range">
                  <input
                    type="number"
                    className="input"
                    placeholder="От"
                    value={localFilters.price_from || ''}
                    onChange={(e) => handleFilterChange('price_from', e.target.value ? parseInt(e.target.value) : null)}
                  />
                  <input
                    type="number"
                    className="input"
                    placeholder="До"
                    value={localFilters.price_to || ''}
                    onChange={(e) => handleFilterChange('price_to', e.target.value ? parseInt(e.target.value) : null)}
                  />
                </div>
              </div>
              <div className="filter-group">
                <label className="filter-label">Площадь от-до, м²</label>
                <div className="area-range">
                  <input
                    type="number"
                    step="0.1"
                    className="input"
                    placeholder="От"
                    value={localFilters.area_from || ''}
                    onChange={(e) => handleFilterChange('area_from', e.target.value ? parseFloat(e.target.value) : null)}
                  />
                  <input
                    type="number"
                    step="0.1"
                    className="input"
                    placeholder="До"
                    value={localFilters.area_to || ''}
                    onChange={(e) => handleFilterChange('area_to', e.target.value ? parseFloat(e.target.value) : null)}
                  />
                </div>
              </div>
            </>
          )}

          {/* Фильтры для паркингов */}
          {objectType === 'parkings' && (
            <div className="filter-group">
              <label className="filter-label">Тип паркинга</label>
              <input
                type="text"
                className="input"
                placeholder="Тип паркинга"
                value={localFilters.parking_type || ''}
                onChange={(e) => handleFilterChange('parking_type', e.target.value)}
              />
            </div>
          )}

          {/* Фильтры для коммерции */}
          {objectType === 'commercial' && (
            <div className="filter-group">
              <label className="filter-label">Назначение</label>
              <input
                type="text"
                className="input"
                placeholder="Назначение помещения"
                value={localFilters.purpose || ''}
                onChange={(e) => handleFilterChange('purpose', e.target.value)}
              />
            </div>
          )}
        </div>

        {/* Расширенные фильтры */}
        {showAdvanced && (
          <div className="advanced-filters">
            <div className="filters-grid">
              <div className="filter-group">
                <label className="filter-label">Сортировка</label>
                <select
                  className="select"
                  value={localFilters.sort || 'price'}
                  onChange={(e) => handleFilterChange('sort', e.target.value)}
                >
                  <option value="price">По цене</option>
                  <option value="deadline">По сроку сдачи</option>
                  <option value="name">По названию</option>
                </select>
              </div>
              <div className="filter-group">
                <label className="filter-label">Направление</label>
                <select
                  className="select"
                  value={localFilters.sort_order || 'asc'}
                  onChange={(e) => handleFilterChange('sort_order', e.target.value)}
                >
                  <option value="asc">По возрастанию</option>
                  <option value="desc">По убыванию</option>
                </select>
              </div>
            </div>
          </div>
        )}

        {/* Кнопки действий */}
        <div className="filters-actions">
          <button className="btn btn-primary" onClick={handleSearch}>
            Поиск
          </button>
          <button className="btn btn-secondary" onClick={handleReset}>
            Сбросить
          </button>
        </div>
      </div>
    </div>
  )
}

// Компонент выбора типа комнат
const RoomTypeSelector = ({ value, onChange }) => {
  const roomTypes = [
    { value: 1, label: 'Студия' },
    { value: 2, label: '1-комн.' },
    { value: 3, label: '2-комн.' },
    { value: 4, label: '3-комн.' },
    { value: 5, label: '4-комн.' },
    { value: 6, label: '5+ комн.' },
  ]

  const handleToggle = (roomValue) => {
    const newValue = value.includes(roomValue)
      ? value.filter(r => r !== roomValue)
      : [...value, roomValue]
    onChange(newValue)
  }

  return (
    <div className="room-type-selector">
      {roomTypes.map((room) => (
        <button
          key={room.value}
          className={`room-type-btn ${value.includes(room.value) ? 'active' : ''}`}
          onClick={() => handleToggle(room.value)}
        >
          {room.label}
        </button>
      ))}
    </div>
  )
}

export default SearchFilters
