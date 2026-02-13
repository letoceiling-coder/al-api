import { useState, useEffect } from 'react'
import { useNavigate, useSearchParams } from 'react-router-dom'
import { trendAgentAPI } from '../services/api'
import { getImageUrl } from '../utils/imageUtils'
import './ObjectsPlans.css'

const ObjectsPlans = () => {
  const navigate = useNavigate()
  const [searchParams] = useSearchParams()
  const [authData, setAuthData] = useState(null)
  const [plans, setPlans] = useState([])
  const [loading, setLoading] = useState(false)
  const [totalCount, setTotalCount] = useState(0)
  const [pagination, setPagination] = useState({
    offset: 0,
    count: 30,
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
      loadPlans()
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

  const loadPlans = async () => {
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
        const normRooms = (r) => {
          if (r == null) return null
          if (typeof r === 'number' && !Number.isNaN(r)) return r
          if (typeof r === 'object') return r.value ?? r.id ?? r.count ?? null
          const n = Number(r)
          return Number.isNaN(n) ? null : n
        }
        // Преобразуем квартиры в планировки
        const plansList = apartmentsList.map(apt => {
          const rooms = normRooms(apt.rooms || apt.room)
          return {
          id: apt.id || apt._id,
          apartment_id: apt.id || apt._id,
          name: apt.name || apt.block_name || apt.title,
          plan_name: `Квартира ${apt.number || apt.apartment_number || ''}`,
          area: apt.privArea || apt.area || apt.area_total,
          area_from: apt.privArea || apt.area || apt.area_total,
          area_to: apt.privArea || apt.area || apt.area_total,
          rooms,
          room_count: rooms,
          // ВАЖНО: Для планировок приоритет - сначала планировка (plan, plan_image), потом фото комплекса
          image: apt.plan || apt.plan_image || apt.image?.url || (apt.images && apt.images.length > 0 ? apt.images[0] : null) || apt.image,
          images: apt.images || [],
          plan: apt.plan || apt.plan_image,
          plan_image: apt.plan_image || apt.plan,
          block_id: apt.block_id || apt._id || apt.id,
          block_guid: apt.guid,
          block_name: apt.block_name || apt.name || apt.title,
        }
        })
        
        setPlans(plansList)
        setTotalCount(response.total_count || plansList.length)
        setPagination(prev => ({
          ...prev,
          hasMore: response.pagination?.has_more || 
                   (pagination.offset + plansList.length < (response.total_count || 0)),
        }))
      }
    } catch (error) {
      console.error('Ошибка загрузки планировок:', error)
      setPlans([])
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

  const handlePageChange = (newPage) => {
    const newOffset = (newPage - 1) * pagination.count
    setPagination(prev => ({ ...prev, page: newPage, offset: newOffset }))
  }

  if (!authData || !authData.authenticated) {
    return (
      <div className="objects-plans-container">
        <div className="loading">
          <div className="spinner"></div>
          <p>Авторизация...</p>
        </div>
      </div>
    )
  }

  return (
    <div className="objects-plans-container">
      <div className="objects-plans-header">
        <button type="button" className="btn btn-outline btn-back" onClick={() => navigate('/')}>
          <svg className="w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden>
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 19l-7-7 7-7" />
          </svg>
          Назад
        </button>
        <h1>Планировки</h1>
      </div>

      <div className="plans-controls">
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
          <p>Загрузка планировок...</p>
        </div>
      ) : (
        <>
          <div className="plans-info">
            Найдено планировок: {totalCount}
          </div>

          <div className="plans-grid">
            {plans.map((plan, idx) => {
              // ВАЖНО: Для планировок приоритет - сначала планировка (plan, plan_image), потом фото комплекса
              const imageUrl = (plan.plan ? getImageUrl(plan.plan) : null) ||
                               (plan.plan_image ? getImageUrl(plan.plan_image) : null) ||
                               (plan.image ? getImageUrl(plan.image) : null) ||
                               (plan.images && plan.images.length > 0 ? getImageUrl(plan.images[0]) : null)
              
              return (
                <div key={plan.id || idx} className="plan-card">
                  {imageUrl && (
                    <div className="plan-card-image">
                      <img src={imageUrl} alt={plan.plan_name || plan.name} />
                    </div>
                  )}
                  <div className="plan-card-content">
                    <h3 className="plan-card-name">{plan.plan_name || plan.name}</h3>
                    <div className="plan-card-info">
                      {(plan.rooms != null && plan.rooms !== '') && (
                        <div className="plan-info-item">
                          <span className="plan-info-label">Комнат:</span>
                          <span className="plan-info-value">
                            {Number(plan.rooms) === 1 ? 'Студия' : `${plan.rooms}-комн.`}
                          </span>
                        </div>
                      )}
                      {plan.area && (
                        <div className="plan-info-item">
                          <span className="plan-info-label">Площадь:</span>
                          <span className="plan-info-value">{plan.area} м²</span>
                        </div>
                      )}
                      {plan.block_name && (
                        <div className="plan-info-item">
                          <span className="plan-info-label">Объект:</span>
                          <span className="plan-info-value">{plan.block_name}</span>
                        </div>
                      )}
                    </div>
                    <div className="plan-card-actions">
                      <a
                        href={`/trendagent/apartments/${plan.block_id}${plan.block_guid ? `?guid=${plan.block_guid}` : ''}`}
                        className="plan-link"
                      >
                        Перейти к объекту
                      </a>
                    </div>
                  </div>
                </div>
              )
            })}
          </div>

          {plans.length === 0 && !loading && (
            <div className="empty-state">
              <p>Планировки не найдены</p>
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

export default ObjectsPlans
