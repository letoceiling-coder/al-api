import './HousesPlans.css'
import { getImageUrl } from '../../utils/imageUtils'

const normRooms = (r) => {
  if (r == null) return null
  if (typeof r === 'number' && !Number.isNaN(r)) return r
  if (typeof r === 'object') return r.value ?? r.id ?? r.count ?? null
  const n = Number(r)
  return Number.isNaN(n) ? null : n
}

const getStatusText = (apt) => {
  if (typeof apt.status === 'string') return apt.status
  if (apt.status?.name) return apt.status.name
  if (apt.status_name) return apt.status_name
  if (apt.booking_status) return apt.booking_status
  const code = apt.status
  const codeMap = { 4: 'Продано', 23: 'Под запрос', 22: 'Забронировано', 1: 'Свободная', 2: 'Свободная' }
  return codeMap[code] || 'Свободная'
}

const getFinishingText = (apt) => {
  return apt.finishing_name || (typeof apt.finishing === 'string' ? apt.finishing : apt.finishing?.name) || null
}

const formatPrice = (price) => {
  if (!price || price === 0) return 'По запросу'
  return new Intl.NumberFormat('ru-RU', { style: 'currency', currency: 'RUB', minimumFractionDigits: 0 }).format(price)
}

const HousesPlans = ({ plansData, apartmentsData, apartments = [], blockId, blockGuid }) => {
  let plans = []

  // Приоритет: явный массив квартир (как на странице объекта — те же данные, что в таблице)
  if (apartments.length > 0) {
    plans = apartments.map(apt => {
      const roomsNum = normRooms(apt.rooms || apt.room)
      const section = apt.section_name || apt.section || (typeof apt.section === 'object' ? apt.section?.name : null)
      const floor = apt.floor ?? apt.floor_number ?? (typeof apt.floor === 'object' ? apt.floor?.value : null)
      const imageSrc = apt.plan || apt.plan_image || apt.image?.url || (apt.images && apt.images[0]) || apt.image
      return {
        id: apt.id || apt._id,
        name: apt.name || apt.plan_name,
        plan_name: `Квартира ${apt.number ?? apt.apartment_number ?? ''}`.trim() || 'План',
        area: apt.privArea ?? apt.area ?? apt.area_total,
        area_from: apt.privArea ?? apt.area ?? apt.area_total,
        area_to: apt.privArea ?? apt.area ?? apt.area_total,
        rooms: roomsNum,
        room_count: roomsNum,
        price: apt.base_price ?? apt.price ?? null,
        section,
        floor,
        finishing: getFinishingText(apt),
        status: getStatusText(apt),
        image: imageSrc,
        plan: apt.plan || apt.plan_image,
        plan_image: apt.plan_image || apt.plan,
        images: apt.images || [],
        apartment_id: apt.id || apt._id,
      }
    })
  } else if (plansData) {
    if (Array.isArray(plansData)) {
      plans = plansData
    } else if (plansData.data && Array.isArray(plansData.data)) {
      plans = plansData.data
    } else if (plansData.results && Array.isArray(plansData.results)) {
      plans = plansData.results
    }
  }

  if (plans.length === 0 && apartmentsData?.data && Array.isArray(apartmentsData.data)) {
    apartmentsData.data.forEach(apt => {
      plans.push({
        name: apt.name || apt.plan_name || 'План',
        area: apt.area,
        rooms: normRooms(apt.rooms || apt.room),
        image: apt.plan || apt.plan_image,
        apartment_id: apt.id || apt._id,
      })
    })
  }

  const hasPlans = plans.length > 0

  if (!hasPlans) {
    return (
      <div className="houses-plans-section">
        <h2>Планы домов</h2>
        <div className="houses-plans-content">
          <p>Планы домов будут отображаться здесь</p>
        </div>
      </div>
    )
  }

  return (
    <div className="houses-plans-section">
      <h2>Планы домов</h2>
      <div className="houses-plans-content">
        <div className="plans-grid">
          {plans.map((plan, index) => {
            const planImage = (plan.plan ? getImageUrl(plan.plan) : null) ||
                              (plan.plan_image ? getImageUrl(plan.plan_image) : null) ||
                              (plan.image ? getImageUrl(plan.image) : null) ||
                              (plan.images?.length > 0 ? getImageUrl(plan.images[0]) : null)
            const planName = plan.plan_name || plan.name || 'План'
            const area = plan.area ?? plan.area_from ?? plan.area_to ?? null
            const roomsNum = plan.rooms != null && plan.rooms !== '' ? normRooms(plan.rooms) : (plan.room_count != null ? normRooms(plan.room_count) : null)
            const roomTypeStr = roomsNum != null ? (Number(roomsNum) === 1 ? 'Студия' : `${roomsNum}-к.кв`) : null
            const sizeStr = [roomTypeStr, area != null && area !== '' ? (typeof area === 'object' && area.from != null && area.to != null ? `${area.from} - ${area.to} м²` : `${area} м²`) : null].filter(Boolean).join(', ')
            const floorSectionStr = [plan.section ? `Секция ${plan.section}` : null, plan.floor != null ? `этаж ${plan.floor}` : null].filter(Boolean).join(', ')
            const statusLower = plan.status ? String(plan.status).toLowerCase() : ''
            const statusClass = statusLower.includes('продан') ? 'plan-card-status_sold' : statusLower.includes('под запрос') ? 'plan-card-status_on-request' : 'plan-card-status_available'

            return (
              <div key={plan.id ?? plan.apartment_id ?? index} className="plan-card">
                {planImage && (
                  <div className="plan-card-image">
                    <img
                      src={planImage}
                      alt={planName}
                      onError={(e) => { e.target.style.display = 'none' }}
                    />
                  </div>
                )}
                <div className="plan-card-content">
                  {planName && <h3 className="plan-card-title">{planName}</h3>}
                  {sizeStr && <div className="plan-card-size">{sizeStr}</div>}
                  {plan.price != null && (
                    <div className="plan-card-price">{formatPrice(plan.price)}</div>
                  )}
                  {floorSectionStr && <div className="plan-card-floor">{floorSectionStr}</div>}
                  {plan.finishing && <div className="plan-card-finishing">{plan.finishing}</div>}
                  {plan.status && (
                    <div className="plan-card-status-holder">
                      <span className={`plan-card-status ${statusClass}`}>{plan.status}</span>
                    </div>
                  )}
                  {(area != null && area !== '' && !sizeStr) || (roomsNum != null && !sizeStr) ? (
                    <div className="plan-card-info">
                      {area != null && area !== '' && (
                        <div className="plan-info-item">
                          <span className="plan-info-label">Площадь:</span>
                          <span className="plan-info-value">
                            {typeof area === 'object' && area.from != null && area.to != null
                              ? `${area.from} - ${area.to} м²`
                              : `${area} м²`}
                          </span>
                        </div>
                      )}
                      {roomsNum != null && (
                        <div className="plan-info-item">
                          <span className="plan-info-label">Комнат:</span>
                          <span className="plan-info-value">{Number(roomsNum) === 1 ? 'Студия' : `${roomsNum}-комн.`}</span>
                        </div>
                      )}
                    </div>
                  ) : null}
                  {blockId && (
                    <div className="plan-card-actions">
                      <a
                        href={`/trendagent/apartments/${blockId}${blockGuid ? `?guid=${blockGuid}` : ''}`}
                        className="plan-link"
                      >
                        Перейти к объекту
                      </a>
                    </div>
                  )}
                </div>
              </div>
            )
          })}
        </div>
      </div>
    </div>
  )
}

export default HousesPlans
