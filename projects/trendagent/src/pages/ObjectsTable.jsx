import { useState, useEffect, useRef } from 'react'
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
  
  // Состояние для drag scroll
  const tableWrapperRef = useRef(null)
  const [isDragging, setIsDragging] = useState(false)
  const [startX, setStartX] = useState(0)
  const [scrollLeft, setScrollLeft] = useState(0)

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
    // Если цена отсутствует, равна 0 или равна 1 (дефолтное значение для "По запросу"), показываем "По запросу"
    if (!price || price === 0 || price === 1) return 'По запросу'
    return new Intl.NumberFormat('ru-RU', {
      style: 'currency',
      currency: 'RUB',
      minimumFractionDigits: 0,
    }).format(price)
  }

  const getImageUrlForApartment = (apt) => {
    // ВАЖНО: Для колонки "План" приоритет - план квартиры, а не фото комплекса
    // Сначала проверяем план квартиры
    if (apt.plan) {
      const planUrl = getImageUrl(apt.plan)
      if (planUrl) return planUrl
    }
    if (apt.plan_image) {
      const planImageUrl = getImageUrl(apt.plan_image)
      if (planImageUrl) return planImageUrl
    }
    
    // Если плана нет, используем другие изображения как fallback
    if (apt.image?.url) return getImageUrl(apt.image.url)
    if (apt.images && Array.isArray(apt.images) && apt.images.length > 0) {
      return getImageUrl(apt.images[0])
    }
    if (apt.image && typeof apt.image === 'string') {
      return getImageUrl(apt.image)
    }
    return null
  }

  const handlePageChange = (newPage) => {
    const newOffset = (newPage - 1) * pagination.count
    setPagination(prev => ({ ...prev, page: newPage, offset: newOffset }))
  }

  // Обработчики для drag scroll
  const handleMouseDown = (e) => {
    if (!tableWrapperRef.current) return
    setIsDragging(true)
    setStartX(e.pageX - tableWrapperRef.current.offsetLeft)
    setScrollLeft(tableWrapperRef.current.scrollLeft)
    e.preventDefault()
  }

  const handleMouseLeave = () => {
    setIsDragging(false)
  }

  const handleMouseUp = () => {
    setIsDragging(false)
  }

  const handleMouseMove = (e) => {
    if (!isDragging || !tableWrapperRef.current) return
    e.preventDefault()
    const x = e.pageX - tableWrapperRef.current.offsetLeft
    const walk = (x - startX) * 2 // Скорость прокрутки
    tableWrapperRef.current.scrollLeft = scrollLeft - walk
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

          <div 
            className="apartments-table-wrapper"
            ref={tableWrapperRef}
            onMouseDown={handleMouseDown}
            onMouseLeave={handleMouseLeave}
            onMouseUp={handleMouseUp}
            onMouseMove={handleMouseMove}
            style={{ cursor: isDragging ? 'grabbing' : 'grab' }}
          >
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
                  <th className="col-district">Район</th>
                  <th className="col-subway">Метро</th>
                  <th className="col-builder">Застройщик</th>
                  <th className="col-deadline">Сдача</th>
                  <th className="col-type">Тип</th>
                  <th className="col-finishing">Отделка</th>
                  <th className="col-price">Цена</th>
                  <th className="col-reward">Вознаграждение</th>
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
                  
                  // Площадь: area_given, privArea, area, area_total
                  const area = typeof apt.area_given === 'number' ? apt.area_given 
                    : (typeof apt.privArea === 'number' ? apt.privArea 
                    : (typeof apt.area === 'number' ? apt.area 
                    : (typeof apt.area_total === 'number' ? apt.area_total : null)))
                  
                  // Кухня: area_kitchen, kitchenArea, kitchen_area
                  const kitchenArea = typeof apt.area_kitchen === 'number' ? apt.area_kitchen 
                    : (typeof apt.kitchenArea === 'number' ? apt.kitchenArea 
                    : (typeof apt.kitchen_area === 'number' ? apt.kitchen_area : null))
                  
                  const finishing = getStringValue(apt.finishing_name || apt.finishing?.name || apt.finishing)
                  const price = typeof apt.base_price === 'number' ? apt.base_price : (typeof apt.price === 'number' ? apt.price : null)
                  
                  // Комнаты: room может быть объектом с name/name_short или числом
                  let rooms = null
                  if (typeof apt.rooms === 'number') {
                    rooms = apt.rooms
                  } else if (typeof apt.room === 'number') {
                    rooms = apt.room
                  } else if (apt.room && typeof apt.room === 'object') {
                    // Если это объект, пытаемся извлечь число из name_short (например, "4Е" -> 4)
                    const roomName = apt.room.name_short || apt.room.name || ''
                    const roomMatch = roomName.match(/^(\d+)/)
                    if (roomMatch) {
                      rooms = parseInt(roomMatch[1])
                    } else {
                      // Если не удалось извлечь число, показываем название
                      rooms = roomName || null
                    }
                  }
                  
                  // Район
                  const district = getStringValue(apt.district?.name || apt.district)
                  
                  // Метро
                  const subway = getStringValue(apt.subway?.name || apt.subway)
                  
                  // Застройщик
                  const builder = getStringValue(apt.builder?.name || apt.builder)
                  
                  // Срок сдачи
                  let deadline = '—'
                  if (apt.deadline) {
                    let deadlineDate = null
                    
                    if (typeof apt.deadline === 'string') {
                      // Если это ISO строка, парсим дату
                      try {
                        deadlineDate = new Date(apt.deadline)
                        if (isNaN(deadlineDate.getTime())) {
                          deadlineDate = null
                        }
                      } catch {
                        deadlineDate = null
                      }
                    } else if (Array.isArray(apt.deadline) && apt.deadline.length > 0) {
                      const deadlineItem = apt.deadline[0]
                      const deadlineStr = deadlineItem.deadline || deadlineItem.value
                      if (deadlineStr) {
                        try {
                          deadlineDate = new Date(deadlineStr)
                          if (isNaN(deadlineDate.getTime())) {
                            deadlineDate = null
                          }
                        } catch {
                          deadlineDate = null
                        }
                      }
                    } else if (typeof apt.deadline === 'object' && apt.deadline !== null) {
                      const deadlineStr = apt.deadline.deadline || apt.deadline.value
                      if (deadlineStr) {
                        try {
                          deadlineDate = new Date(deadlineStr)
                          if (isNaN(deadlineDate.getTime())) {
                            deadlineDate = null
                          }
                        } catch {
                          deadlineDate = null
                        }
                      }
                    }
                    
                    // Проверяем, прошла ли дата
                    if (deadlineDate) {
                      const now = new Date()
                      now.setHours(0, 0, 0, 0)
                      deadlineDate.setHours(0, 0, 0, 0)
                      
                      if (deadlineDate < now) {
                        deadline = 'Сдан'
                      } else {
                        deadline = deadlineDate.toLocaleDateString('ru-RU', { year: 'numeric', month: 'short', day: 'numeric' })
                      }
                    } else if (typeof apt.deadline === 'string') {
                      deadline = apt.deadline
                    }
                  }
                  
                  // Тип (is_suite, exclusive и т.д.)
                  let type = '—'
                  if (apt.is_suite) {
                    type = 'Апартаменты'
                  } else if (apt.exclusive) {
                    type = 'Эксклюзив'
                  } else if (rooms && typeof rooms === 'number') {
                    type = rooms === 1 ? 'Студия' : `${rooms}-комн.`
                  }
                  
                  // Вознаграждение
                  const reward = getStringValue(apt.reward?.label || apt.reward)
                  
                  const status = typeof apt.status === 'string' 
                    ? apt.status 
                    : (typeof apt.status === 'object' && apt.status !== null
                      ? (apt.status.name || apt.status.title || String(apt.status))
                      : (apt.booking_status || 'Свободна'))
                  // ID блока и квартиры
                  const blockId = apt.block_id || apt._id || apt.id
                  const blockGuid = apt.guid || apt.block_guid
                  const apartmentId = apt._id || apt.id
                  
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
                      <td className="col-rooms">
                        {rooms !== null 
                          ? (typeof rooms === 'number' 
                            ? (rooms === 1 ? 'Студия' : `${rooms}-комн.`) 
                            : rooms)
                          : '—'}
                      </td>
                      <td className="col-area">{area ? `${area.toFixed(2)} м²` : '—'}</td>
                      <td className="col-kitchen">{kitchenArea ? `${kitchenArea.toFixed(2)} м²` : '—'}</td>
                      <td className="col-district">{district}</td>
                      <td className="col-subway">{subway}</td>
                      <td className="col-builder">{builder}</td>
                      <td className="col-deadline">{deadline}</td>
                      <td className="col-type">{type}</td>
                      <td className="col-finishing">{finishing}</td>
                      <td className="col-price">{price ? formatPrice(price) : 'По запросу'}</td>
                      <td className="col-reward">{reward}</td>
                      <td className="col-status">
                        <span className={`status-badge status-${typeof status === 'string' ? status.toLowerCase().replace(/\s+/g, '-') : 'default'}`}>
                          {status}
                        </span>
                      </td>
                      <td className="col-actions">
                        <a
                          href={`/trendagent/apartments/${blockId}/flat/${apt._id || apt.id}${blockGuid ? `?guid=${blockGuid}` : ''}`}
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
