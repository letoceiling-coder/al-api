import './HousesPlans.css'
import { Link } from 'react-router-dom'
import { getImageUrl } from '../../utils/imageUtils'

const normRooms = (r) => {
  if (r == null) return null
  if (typeof r === 'number' && !Number.isNaN(r)) return r
  if (typeof r === 'object') return r.value ?? r.id ?? r.count ?? null
  const n = Number(r)
  return Number.isNaN(n) ? null : n
}

const HousesPlans = ({ plansData, apartmentsData, apartments = [], blockId, blockGuid }) => {
  let plans = []

  // Приоритет: явный массив квартир (как на странице объекта — те же данные, что в таблице)
  if (apartments.length > 0) {
    plans = apartments.map(apt => {
      const roomsNum = normRooms(apt.rooms || apt.room)
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
            const roomsLabel = roomsNum != null ? (Number(roomsNum) === 1 ? 'Студия' : `${roomsNum}-комн.`) : null

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
                    {roomsLabel && (
                      <div className="plan-info-item">
                        <span className="plan-info-label">Комнат:</span>
                        <span className="plan-info-value">{roomsLabel}</span>
                      </div>
                    )}
                  </div>
                  {blockId && (
                    <div className="plan-card-actions">
                      <Link
                        to={`/trendagent/apartments/${blockId}${blockGuid ? `?guid=${blockGuid}` : ''}`}
                        className="plan-link"
                      >
                        Перейти к объекту
                      </Link>
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
