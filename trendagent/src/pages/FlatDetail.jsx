import { useState, useEffect } from 'react'
import { useParams, useNavigate, useSearchParams } from 'react-router-dom'
import { trendAgentAPI } from '../services/api'
import { getImageUrl } from '../utils/imageUtils'
import './FlatDetail.css'

const FlatDetail = () => {
  const { id, apartmentId } = useParams()
  const navigate = useNavigate()
  const [searchParams] = useSearchParams()
  const blockId = searchParams.get('block') || id

  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const [apartmentData, setApartmentData] = useState(null)
  const [blockData, setBlockData] = useState(null)
  const [phone, setPhone] = useState('+79045393434')
  const [password, setPassword] = useState('nwBvh4q')

  useEffect(() => {
    loadFlatDetail()
  }, [blockId, apartmentId])

  const loadFlatDetail = async () => {
    setLoading(true)
    setError(null)

    try {
      const params = {
        phone,
        password,
      }

      const response = await trendAgentAPI.getApartmentFlatDetail(blockId, apartmentId, params)

      if (response.success) {
        setApartmentData(response.data?.apartment || response.data)
        setBlockData(response.data?.block || null)
      } else {
        setError(response.message || 'Ошибка загрузки данных квартиры')
      }
    } catch (err) {
      console.error('Ошибка загрузки данных квартиры:', err)
      setError(err.message || 'Ошибка загрузки данных квартиры')
    } finally {
      setLoading(false)
    }
  }

  const formatPrice = (price) => {
    if (!price || price === 0) return 'По запросу'
    return new Intl.NumberFormat('ru-RU', {
      style: 'currency',
      currency: 'RUB',
      minimumFractionDigits: 0,
    }).format(price)
  }

  const formatPricePerM2 = (price, area) => {
    if (!price || !area || area === 0) return '—'
    const perM2 = Math.round(price / area)
    return new Intl.NumberFormat('ru-RU', {
      style: 'currency',
      currency: 'RUB',
      minimumFractionDigits: 0,
    }).format(perM2)
  }

  const getStatus = (apt) => {
    if (!apt) return 'Свободна'
    if (typeof apt.status === 'string') {
      return apt.status
    }
    if (apt.status?.name) {
      return apt.status.name
    }
    if (apt.booking_status) {
      return apt.booking_status
    }
    if (apt.is_booked) {
      return 'Забронирована'
    }
    return 'Свободна'
  }

  const getStatusClass = (status) => {
    const statusLower = String(status).toLowerCase().replace(/\s+/g, '-')
    if (statusLower.includes('свободн') || statusLower.includes('available')) {
      return 'status-available'
    }
    if (statusLower.includes('забронирован') || statusLower.includes('booked')) {
      return 'status-booked'
    }
    if (statusLower.includes('продан') || statusLower.includes('sold')) {
      return 'status-sold'
    }
    return 'status-default'
  }

  if (loading) {
    return (
      <div className="flat-detail-container">
        <div className="loading">
          <div className="spinner"></div>
          <p>Загрузка данных...</p>
        </div>
      </div>
    )
  }

  if (error) {
    return (
      <div className="flat-detail-container">
        <div className="error-message">
          <p>{error}</p>
          <button className="btn btn-primary" onClick={() => navigate(-1)}>
            Назад
          </button>
        </div>
      </div>
    )
  }

  if (!apartmentData) {
    return (
      <div className="flat-detail-container">
        <div className="error-message">
          <p>Данные квартиры не найдены</p>
          <button className="btn btn-primary" onClick={() => navigate(-1)}>
            Назад
          </button>
        </div>
      </div>
    )
  }

  const apartment = apartmentData.data || apartmentData
  const block = blockData?.data || blockData

  const imageUrl = apartment.image?.url || 
                  (apartment.images && apartment.images.length > 0 ? getImageUrl(apartment.images[0]) : null) ||
                  (apartment.image && typeof apartment.image === 'string' ? apartment.image : null) ||
                  (apartment.plan ? getImageUrl(apartment.plan) : null) ||
                  (apartment.plan_image ? getImageUrl(apartment.plan_image) : null)

  const number = apartment.number || apartment.apartment_number || '—'
  const floor = apartment.floor || '—'
  const section = apartment.section_name || apartment.section || '—'
  const building = apartment.building_name || apartment.building || apartment.corpus || '—'
  const area = apartment.privArea || apartment.area || apartment.area_total || null
  const kitchenArea = apartment.kitchenArea || apartment.kitchen_area || null
  const livingArea = apartment.livingArea || apartment.living_area || null
  const finishing = apartment.finishing_name || apartment.finishing || '—'
  const basePrice = apartment.base_price || null
  const fullPrice = apartment.price || apartment.full_price || null
  const pricePerM2 = area && basePrice ? formatPricePerM2(basePrice, area) : '—'
  const status = getStatus(apartment)
  const rooms = apartment.rooms || apartment.room || null
  const view = apartment.view_image || apartment.view || null
  const exclusive = apartment.exclusive || apartment.is_exclusive || false

  return (
    <div className="flat-detail-container">
      {/* Хлебные крошки */}
      <div className="breadcrumbs">
        <button className="btn-back" onClick={() => navigate(-1)}>
          <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
            <path d="M12.5 15L7.5 10L12.5 5" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
          </svg>
          Назад
        </button>
        {block && (
          <span className="breadcrumb-separator">/</span>
        )}
        {block && (
          <span className="breadcrumb-item">{block.name || block.title || 'Объект'}</span>
        )}
        <span className="breadcrumb-separator">/</span>
        <span className="breadcrumb-item active">Квартира {number}</span>
      </div>

      {/* Заголовок */}
      <div className="flat-header">
        <h1>Квартира {number}</h1>
        {block && (
          <p className="block-name">{block.name || block.title}</p>
        )}
      </div>

      <div className="flat-content">
        <div className="flat-main">
          {/* План квартиры */}
          {imageUrl && (
            <div className="flat-plan-section">
              <h2>Планировка</h2>
              <div className="flat-plan-image">
                <img 
                  src={imageUrl} 
                  alt={`Планировка квартиры ${number}`}
                  onError={(e) => { e.target.style.display = 'none' }}
                />
              </div>
            </div>
          )}

          {/* Характеристики */}
          <div className="flat-specs-section">
            <h2>Характеристики</h2>
            <div className="specs-grid">
              {rooms && (
                <div className="spec-item">
                  <span className="spec-label">Комнат:</span>
                  <span className="spec-value">{rooms === 1 ? 'Студия' : `${rooms}-комн.`}</span>
                </div>
              )}
              {area && (
                <div className="spec-item">
                  <span className="spec-label">Общая площадь:</span>
                  <span className="spec-value">{area} м²</span>
                </div>
              )}
              {livingArea && (
                <div className="spec-item">
                  <span className="spec-label">Жилая площадь:</span>
                  <span className="spec-value">{livingArea} м²</span>
                </div>
              )}
              {kitchenArea && (
                <div className="spec-item">
                  <span className="spec-label">Площадь кухни:</span>
                  <span className="spec-value">{kitchenArea} м²</span>
                </div>
              )}
              <div className="spec-item">
                <span className="spec-label">Этаж:</span>
                <span className="spec-value">{floor}</span>
              </div>
              {section !== '—' && (
                <div className="spec-item">
                  <span className="spec-label">Секция:</span>
                  <span className="spec-value">{section}</span>
                </div>
              )}
              {building !== '—' && (
                <div className="spec-item">
                  <span className="spec-label">Корпус:</span>
                  <span className="spec-value">{building}</span>
                </div>
              )}
              <div className="spec-item">
                <span className="spec-label">Отделка:</span>
                <span className="spec-value">{finishing}</span>
              </div>
              {view && (
                <div className="spec-item">
                  <span className="spec-label">Вид:</span>
                  <span className="spec-value">{view}</span>
                </div>
              )}
              <div className="spec-item">
                <span className="spec-label">Эксклюзив:</span>
                <span className="spec-value">{exclusive ? 'Да' : 'Нет'}</span>
              </div>
            </div>
          </div>
        </div>

        <div className="flat-sidebar">
          {/* Цены */}
          <div className="flat-prices-section">
            <h2>Цены</h2>
            {basePrice && (
              <div className="price-item">
                <span className="price-label">Базовая цена:</span>
                <span className="price-value">{formatPrice(basePrice)}</span>
              </div>
            )}
            {fullPrice && fullPrice !== basePrice && (
              <div className="price-item">
                <span className="price-label">Цена при 100%:</span>
                <span className="price-value">{formatPrice(fullPrice)}</span>
              </div>
            )}
            {pricePerM2 !== '—' && (
              <div className="price-item">
                <span className="price-label">Цена за м²:</span>
                <span className="price-value">{pricePerM2}</span>
              </div>
            )}
          </div>

          {/* Статус */}
          <div className="flat-status-section">
            <h2>Статус</h2>
            <div className={`status-badge ${getStatusClass(status)}`}>
              {status}
            </div>
          </div>

          {/* Действия */}
          <div className="flat-actions-section">
            <button className="btn btn-primary btn-block">
              Зафиксировать клиента
            </button>
            <button className="btn btn-secondary btn-block">
              Добавить к сравнению
            </button>
          </div>
        </div>
      </div>
    </div>
  )
}

export default FlatDetail
