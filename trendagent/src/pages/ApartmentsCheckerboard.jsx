import { useState, useEffect } from 'react'
import { useParams, useNavigate, useSearchParams } from 'react-router-dom'
import { trendAgentAPI } from '../services/api'
import { getImageUrl } from '../utils/imageUtils'
import './HousesCheckerboard.css'

const ApartmentsCheckerboard = () => {
  const { id } = useParams()
  const navigate = useNavigate()
  const [searchParams] = useSearchParams()
  const guid = searchParams.get('guid')

  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const [buildings, setBuildings] = useState([])
  const [selectedBuilding, setSelectedBuilding] = useState(null)
  const [apartments, setApartments] = useState(null)
  const [phone, setPhone] = useState('+79045393434')
  const [password, setPassword] = useState('nwBvh4q')

  useEffect(() => {
    loadBuildings()
  }, [id])

  useEffect(() => {
    if (selectedBuilding) {
      loadApartments()
    }
  }, [selectedBuilding, id])

  const loadBuildings = async () => {
    setLoading(true)
    setError(null)

    try {
      const params = {
        phone,
        password,
        // Для квартир можно передать room параметры из query
        room: searchParams.getAll('room').length > 0 
          ? searchParams.getAll('room').map(r => parseInt(r))
          : undefined,
      }

      const response = await trendAgentAPI.getApartmentsCheckerboardBuildings(id, params)

      if (response.success) {
        const buildingsData = Array.isArray(response.data) 
          ? response.data 
          : (response.data?.buildings ? response.data.buildings : [])
        setBuildings(buildingsData)
        
        // Автоматически выбираем первый корпус, если есть
        if (buildingsData.length > 0 && !selectedBuilding) {
          setSelectedBuilding(buildingsData[0].id || buildingsData[0]._id || buildingsData[0].building_id)
        }
      } else {
        setError(response.message || 'Ошибка загрузки корпусов')
      }
    } catch (err) {
      console.error('Ошибка загрузки корпусов:', err)
      setError(err.message || 'Ошибка загрузки корпусов')
    } finally {
      setLoading(false)
    }
  }

  const loadApartments = async () => {
    if (!selectedBuilding) return

    setLoading(true)
    setError(null)

    try {
      const params = {
        phone,
        password,
        building_id: selectedBuilding,
      }

      const response = await trendAgentAPI.getApartmentsCheckerboardApartments(id, params)

      if (response.success) {
        setApartments(response.data)
      } else {
        setError(response.message || 'Ошибка загрузки квартир')
      }
    } catch (err) {
      console.error('Ошибка загрузки квартир:', err)
      setError(err.message || 'Ошибка загрузки квартир')
    } finally {
      setLoading(false)
    }
  }

  const formatPrice = (price) => {
    if (!price || price === 0) return '—'
    return new Intl.NumberFormat('ru-RU', {
      style: 'currency',
      currency: 'RUB',
      minimumFractionDigits: 0,
    }).format(price)
  }

  if (loading && !apartments) {
    return (
      <div className="checkerboard-container">
        <div className="loading">
          <div className="spinner"></div>
          <p>Загрузка данных...</p>
        </div>
      </div>
    )
  }

  if (error) {
    return (
      <div className="checkerboard-container">
        <div className="error-message">
          <p>{error}</p>
          <button className="btn btn-primary" onClick={() => navigate(-1)}>
            Назад
          </button>
        </div>
      </div>
    )
  }

  return (
    <div className="checkerboard-container">
      <div className="checkerboard-header">
        <button className="btn-back" onClick={() => navigate(-1)}>
          <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
            <path d="M12.5 15L7.5 10L12.5 5" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
          </svg>
          Назад
        </button>
        <h1>Квартиры на шахматке</h1>
      </div>

      {/* Выбор корпуса */}
      {buildings.length > 0 && (
        <div className="buildings-selector">
          <label>Выберите корпус:</label>
          <select
            value={selectedBuilding || ''}
            onChange={(e) => setSelectedBuilding(e.target.value)}
            className="building-select"
          >
            <option value="">Выберите корпус</option>
            {buildings.map((building) => {
              const buildingId = building.id || building._id || building.building_id
              const buildingName = building.name || building.building_name || `Корпус ${buildingId}`
              const apartmentsCount = building.apartments_count || building.count || 0
              
              return (
                <option key={buildingId} value={buildingId}>
                  {buildingName} ({apartmentsCount} {apartmentsCount === 1 ? 'квартира' : apartmentsCount < 5 ? 'квартиры' : 'квартир'})
                </option>
              )
            })}
          </select>
        </div>
      )}

      {/* Сетка квартир (шахматка) */}
      {selectedBuilding && apartments && (
        <div className="checkerboard-content">
          {Array.isArray(apartments) && apartments.length > 0 ? (
            <div className="checkerboard-grid">
              {apartments.map((apartment, index) => {
                const imageUrl = apartment.image?.url || 
                                (apartment.images && apartment.images.length > 0 ? getImageUrl(apartment.images[0]) : null) ||
                                (apartment.image && typeof apartment.image === 'string' ? apartment.image : null)
                const number = apartment.number || apartment.apartment_number || '—'
                const floor = apartment.floor || '—'
                const section = apartment.section_name || apartment.section || '—'
                const area = apartment.privArea || apartment.area || apartment.area_total || null
                const price = apartment.price || apartment.base_price || null
                const status = apartment.status?.name || apartment.status || apartment.booking_status || 'Свободная'
                
                return (
                  <div key={index} className="checkerboard-cell">
                    {imageUrl && (
                      <div className="cell-image">
                        <img 
                          src={imageUrl} 
                          alt={`Квартира ${number}`}
                          onError={(e) => { e.target.style.display = 'none' }}
                        />
                      </div>
                    )}
                    <div className="cell-content">
                      <div className="cell-number">№ {number}</div>
                      <div className="cell-info">
                        <div>Этаж: {floor}</div>
                        {section !== '—' && <div>Секция: {section}</div>}
                        {area && <div>Площадь: {area} м²</div>}
                        {price && <div className="cell-price">{formatPrice(price)}</div>}
                      </div>
                      <div className={`cell-status status-${status.toLowerCase().replace(/\s+/g, '-')}`}>
                        {status}
                      </div>
                    </div>
                  </div>
                )
              })}
            </div>
          ) : (
            <div className="empty-state">
              <p>Квартиры не найдены</p>
            </div>
          )}
        </div>
      )}

      {!selectedBuilding && (
        <div className="empty-state">
          <p>Выберите корпус для отображения квартир</p>
        </div>
      )}
    </div>
  )
}

export default ApartmentsCheckerboard
