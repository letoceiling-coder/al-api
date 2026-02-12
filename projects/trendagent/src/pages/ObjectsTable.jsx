import { useState, useEffect } from 'react'
import { useNavigate, useSearchParams } from 'react-router-dom'
import { trendAgentAPI } from '../services/api'
import { getImageUrl } from '../utils/imageUtils'
import './ObjectsTable.css'

const ObjectsTable = () => {
  const navigate = useNavigate()
  const [searchParams] = useSearchParams()
  const [authData, setAuthData] = useState(null)
  const [apartments, setApartments] = useState([])
  const [loading, setLoading] = useState(false)
  const [totalCount, setTotalCount] = useState(0)
  const [pagination, setPagination] = useState({
    offset: 0,
    count: 50,
    page: 1,
    hasMore: false,
  })
  const [filters, setFilters] = useState({
    city: '58c665588b6aa52311afa01b',
    phone: '+79045393434',
    password: 'nwBvh4q',
    room: searchParams.getAll('room').map(r => parseInt(r)).filter(Boolean),
  })
  const [sortBy, setSortBy] = useState('price')
  const [sortOrder, setSortOrder] = useState('asc')

  useEffect(() => {
    authenticate()
  }, [])

  useEffect(() => {
    if (authData && authData.authenticated) {
      loadApartments()
    }
  }, [authData, pagination.offset, filters, sortBy, sortOrder])

  const authenticate = async () => {
    try {
      const response = await trendAgentAPI.authenticate(
        filters.phone,
        filters.password
      )
      if (response.success && response.data.authenticated) {
        setAuthData(response.data)
      }
    } catch (error) {
      console.error('Ошибка авторизации:', error)
    }
  }

  const loadApartments = async () => {
    if (!authData || !authData.authenticated) return

    setLoading(true)
    try {
      const params = {
        phone: filters.phone,
        password: filters.password,
        city: filters.city,
        count: pagination.count,
        offset: pagination.offset,
        sort: sortBy,
        sort_order: sortOrder,
        room: filters.room,
      }

      const response = await trendAgentAPI.getApartments(params)

      if (response.success) {
        const apartmentsList = response.data?.objects || response.data?.data || []
        setApartments(apartmentsList)
        setTotalCount(response.total_count || apartmentsList.length)
        setPagination(prev => ({
          ...prev,
          hasMore: response.pagination?.has_more || 
                   (pagination.offset + apartmentsList.length < (response.total_count || 0)),
        }))
      }
    } catch (error) {
      console.error('Ошибка загрузки квартир:', error)
      setApartments([])
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

  const getImageUrlForApartment = (apt) => {
    if (apt.image?.url) return getImageUrl(apt.image.url)
    if (apt.images && Array.isArray(apt.images) && apt.images.length > 0) {
      return getImageUrl(apt.images[0])
    }
    if (apt.image && typeof apt.image === 'string') {
      return getImageUrl(apt.image)
    }
    if (apt.plan) return getImageUrl(apt.plan)
    if (apt.plan_image) return getImageUrl(apt.plan_image)
    return null
  }

  const handlePageChange = (newPage) => {
    const newOffset = (newPage - 1) * pagination.count
    setPagination(prev => ({ ...prev, page: newPage, offset: newOffset }))
  }

  if (!authData || !authData.authenticated) {
    return (
      <div className="objects-table-container">
        <div className="loading">
          <div className="spinner"></div>
          <p>Авторизация...</p>
        </div>
      </div>
    )
  }

  return (
    <div className="objects-table-container">
      <div className="objects-table-header">
        <button className="btn-back" onClick={() => navigate('/')}>
          <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
            <path d="M12.5 15L7.5 10L12.5 5" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
          </svg>
          Назад
        </button>
        <h1>Квартиры</h1>
      </div>

      <div className="table-controls">
        <div className="sort-controls">
          <label>Сортировка:</label>
          <select
            value={sortBy}
            onChange={(e) => setSortBy(e.target.value)}
            className="sort-select"
          >
            <option value="price">По цене</option>
            <option value="deadline">По сроку сдачи</option>
            <option value="name">По названию</option>
          </select>
          <button
            className="sort-order-btn"
            onClick={() => setSortOrder(sortOrder === 'asc' ? 'desc' : 'asc')}
          >
            {sortOrder === 'asc' ? '↑' : '↓'}
          </button>
        </div>
      </div>

      {loading ? (
        <div className="loading">
          <div className="spinner"></div>
          <p>Загрузка квартир...</p>
        </div>
      ) : (
        <>
          <div className="table-info">
            Найдено квартир: {totalCount}
          </div>

          <div className="apartments-table-wrapper">
            <table className="apartments-table">
              <thead>
                <tr>
                  <th className="col-image">План</th>
                  <th className="col-object">Объект</th>
                  <th className="col-corpus">Корпус</th>
                  <th className="col-section">Секция</th>
                  <th className="col-floor">Этаж</th>
                  <th className="col-number">№ кв.</th>
                  <th className="col-rooms">Комнат</th>
                  <th className="col-area">Площадь</th>
                  <th className="col-kitchen">Кухня</th>
                  <th className="col-finishing">Отделка</th>
                  <th className="col-price">Цена</th>
                  <th className="col-status">Статус</th>
                  <th className="col-actions">Действия</th>
                </tr>
              </thead>
              <tbody>
                {apartments.map((apt, idx) => {
                  const imageUrl = getImageUrlForApartment(apt)
                  
                  // Безопасное извлечение примитивных значений (защита от объектов)
                  const getStringValue = (value, fallback = '—') => {
                    if (value === null || value === undefined) return fallback
                    if (typeof value === 'string') return value
                    if (typeof value === 'number') return String(value)
                    if (typeof value === 'object') {
                      // Если это объект, пытаемся извлечь строковое значение
                      return value.name || value.title || value.value || String(value) || fallback
                    }
                    return String(value) || fallback
                  }
                  
                  const number = getStringValue(apt.number || apt.apartment_number)
                  const floor = getStringValue(apt.floor)
                  const section = getStringValue(apt.section_name || apt.section)
                  const building = getStringValue(apt.building_name || apt.building || apt.corpus)
                  const blockName = getStringValue(apt.block_name || apt.name || apt.title)
                  const area = typeof apt.privArea === 'number' ? apt.privArea : (typeof apt.area === 'number' ? apt.area : (typeof apt.area_total === 'number' ? apt.area_total : null))
                  const kitchenArea = typeof apt.kitchenArea === 'number' ? apt.kitchenArea : (typeof apt.kitchen_area === 'number' ? apt.kitchen_area : null)
                  const finishing = getStringValue(apt.finishing_name || apt.finishing)
                  const price = typeof apt.base_price === 'number' ? apt.base_price : (typeof apt.price === 'number' ? apt.price : null)
                  const rooms = typeof apt.rooms === 'number' ? apt.rooms : (typeof apt.room === 'number' ? apt.room : null)
                  const status = typeof apt.status === 'string' 
                    ? apt.status 
                    : (typeof apt.status === 'object' && apt.status !== null
                      ? (apt.status.name || apt.status.title || String(apt.status))
                      : (apt.booking_status || 'Свободна'))
                  const blockId = apt.block_id || apt._id || apt.id
                  const blockGuid = apt.guid
                  
                  return (
                    <tr key={apt.id || apt._id || idx}>
                      <td className="col-image">
                        {imageUrl ? (
                          <img src={imageUrl} alt={`План ${number}`} className="plan-thumbnail" />
                        ) : (
                          <div className="plan-placeholder">—</div>
                        )}
                      </td>
                      <td className="col-object">
                        <a
                          href={`/trendagent/apartments/${blockId}${blockGuid ? `?guid=${blockGuid}` : ''}`}
                          className="object-link"
                        >
                          {blockName}
                        </a>
                      </td>
                      <td className="col-corpus">{building}</td>
                      <td className="col-section">{section}</td>
                      <td className="col-floor">{floor}</td>
                      <td className="col-number">{number}</td>
                      <td className="col-rooms">{rooms ? (rooms === 1 ? 'Студия' : `${rooms}-комн.`) : '—'}</td>
                      <td className="col-area">{area ? `${area} м²` : '—'}</td>
                      <td className="col-kitchen">{kitchenArea ? `${kitchenArea} м²` : '—'}</td>
                      <td className="col-finishing">{finishing}</td>
                      <td className="col-price">{price ? formatPrice(price) : 'По запросу'}</td>
                      <td className="col-status">
                        <span className={`status-badge status-${typeof status === 'string' ? status.toLowerCase().replace(/\s+/g, '-') : 'default'}`}>
                          {status}
                        </span>
                      </td>
                      <td className="col-actions">
                        <a
                          href={`/trendagent/apartments/${blockId}/flat/${apt.id || apt._id}${blockGuid ? `?block=${blockId}&guid=${blockGuid}` : `?block=${blockId}`}`}
                          className="flat-link"
                          title="Детальная информация"
                        >
                          <svg width="16" height="16" viewBox="0 0 16 16" fill="none">
                            <path d="M6 3L11 8L6 13" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
                          </svg>
                        </a>
                      </td>
                    </tr>
                  )
                })}
              </tbody>
            </table>
          </div>

          {apartments.length === 0 && !loading && (
            <div className="empty-state">
              <p>Квартиры не найдены</p>
            </div>
          )}

          {totalCount > 0 && (
            <div className="pagination">
              <button
                className="btn btn-secondary"
                disabled={pagination.page === 1}
                onClick={() => handlePageChange(pagination.page - 1)}
              >
                Назад
              </button>
              <span className="pagination-info">
                Страница {pagination.page} из {Math.ceil(totalCount / pagination.count)}
              </span>
              <button
                className="btn btn-secondary"
                disabled={!pagination.hasMore}
                onClick={() => handlePageChange(pagination.page + 1)}
              >
                Вперед
              </button>
            </div>
          )}
        </>
      )}
    </div>
  )
}

export default ObjectsTable
