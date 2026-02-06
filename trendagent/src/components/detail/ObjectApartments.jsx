import { useState } from 'react'
import './ObjectApartments.css'
import { getImageUrl } from '../../utils/imageUtils'

const ObjectApartments = ({ apartmentsData, plansData, buildingsData }) => {
  const [selectedRoomType, setSelectedRoomType] = useState(null)
  const [selectedBuilding, setSelectedBuilding] = useState(null)

  // Безопасное извлечение данных с проверкой на массив
  const apartments = Array.isArray(apartmentsData?.data) 
    ? apartmentsData.data 
    : (Array.isArray(apartmentsData) ? apartmentsData : [])
  const plans = Array.isArray(plansData?.data) 
    ? plansData.data 
    : (Array.isArray(plansData) ? plansData : [])
  const buildings = Array.isArray(buildingsData?.data) 
    ? buildingsData.data 
    : (Array.isArray(buildingsData) ? buildingsData : [])

  const roomTypes = [...new Set(apartments.map(apt => apt.rooms || apt.room).filter(Boolean))].sort()

  const filteredApartments = apartments.filter(apt => {
    if (selectedRoomType && (apt.rooms || apt.room) !== selectedRoomType) return false
    if (selectedBuilding && apt.building_name !== selectedBuilding) return false
    return true
  })

  const formatPrice = (price) => {
    if (!price || price === 0) return 'По запросу'
    return new Intl.NumberFormat('ru-RU', {
      style: 'currency',
      currency: 'RUB',
      minimumFractionDigits: 0,
    }).format(price)
  }

  const getPlanImage = (apartment) => {
    if (apartment.plan) {
      return getImageUrl(apartment.plan)
    }
    return null
  }

  return (
    <div className="object-apartments card">
      <h2 className="section-title">Квартиры</h2>
      
      <div className="apartments-stats">
        <div className="stat-item">
          <span className="stat-label">Всего квартир:</span>
          <span className="stat-value">{apartmentsData?.total || apartments.length}</span>
        </div>
        {apartmentsData?.on_request_count > 0 && (
          <div className="stat-item">
            <span className="stat-label">Под запрос:</span>
            <span className="stat-value">{apartmentsData.on_request_count}</span>
          </div>
        )}
        {apartmentsData?.booked_count > 0 && (
          <div className="stat-item">
            <span className="stat-label">Забронировано:</span>
            <span className="stat-value">{apartmentsData.booked_count}</span>
          </div>
        )}
      </div>

      {/* Фильтры */}
      <div className="apartments-filters">
        {roomTypes.length > 0 && (
          <div className="filter-group">
            <label className="filter-label">Тип квартиры:</label>
            <select
              className="select"
              value={selectedRoomType || ''}
              onChange={(e) => setSelectedRoomType(e.target.value || null)}
            >
              <option value="">Все</option>
              {roomTypes.map(room => (
                <option key={room} value={room}>
                  {room === 1 ? 'Студия' : `${room}-комн.`}
                </option>
              ))}
            </select>
          </div>
        )}

        {buildings.length > 0 && (
          <div className="filter-group">
            <label className="filter-label">Здание:</label>
            <select
              className="select"
              value={selectedBuilding || ''}
              onChange={(e) => setSelectedBuilding(e.target.value || null)}
            >
              <option value="">Все</option>
              {buildings.map((building, index) => (
                <option key={index} value={building.name || building.building_name}>
                  {building.name || building.building_name || `Здание ${index + 1}`}
                </option>
              ))}
            </select>
          </div>
        )}
      </div>

      {/* Список квартир */}
      <div className="apartments-grid">
        {filteredApartments.map((apartment, index) => (
          <div key={apartment._id || index} className="apartment-card">
            {getPlanImage(apartment) && (
              <div className="apartment-image">
                <img src={getPlanImage(apartment)} alt={`Планировка ${apartment.number || index + 1}`} />
              </div>
            )}
            <div className="apartment-content">
              <h3 className="apartment-number">Квартира {apartment.number || index + 1}</h3>
              <div className="apartment-info">
                <div className="info-row">
                  <span className="info-label">Комнат:</span>
                  <span className="info-value">{apartment.rooms || apartment.room || '-'}</span>
                </div>
                <div className="info-row">
                  <span className="info-label">Площадь:</span>
                  <span className="info-value">
                    {apartment.privArea || apartment.area || apartment.area_total || '-'} м²
                  </span>
                </div>
                {apartment.kitchenArea && (
                  <div className="info-row">
                    <span className="info-label">Кухня:</span>
                    <span className="info-value">{apartment.kitchenArea} м²</span>
                  </div>
                )}
                <div className="info-row">
                  <span className="info-label">Этаж:</span>
                  <span className="info-value">{apartment.floor || '-'}</span>
                </div>
                {apartment.section_name && (
                  <div className="info-row">
                    <span className="info-label">Секция:</span>
                    <span className="info-value">{apartment.section_name}</span>
                  </div>
                )}
                {apartment.building_name && (
                  <div className="info-row">
                    <span className="info-label">Здание:</span>
                    <span className="info-value">{apartment.building_name}</span>
                  </div>
                )}
              </div>
              <div className="apartment-price">
                {formatPrice(apartment.price || apartment.base_price)}
              </div>
              {apartment.status && (
                <div className="apartment-status">
                  {apartment.status.name || apartment.status}
                </div>
              )}
            </div>
          </div>
        ))}
      </div>

      {filteredApartments.length === 0 && (
        <div className="empty-state">
          <p>Квартиры не найдены</p>
        </div>
      )}
    </div>
  )
}

export default ObjectApartments
