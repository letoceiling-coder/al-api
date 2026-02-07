import './HousesPlans.css'
import { getImageUrl } from '../../utils/imageUtils'

const HousesPlans = ({ plansData, apartmentsData }) => {
  // Получаем планы из plansData или apartmentsData
  let plans = []
  
  if (plansData) {
    if (Array.isArray(plansData)) {
      plans = plansData
    } else if (plansData.data && Array.isArray(plansData.data)) {
      plans = plansData.data
    } else if (plansData.results && Array.isArray(plansData.results)) {
      plans = plansData.results
    }
  }
  
  // Если планов нет в plansData, пробуем извлечь из apartmentsData
  if (plans.length === 0 && apartmentsData) {
    // Планы могут быть в apartments.data как отдельные объекты с планами
    if (apartmentsData.data && Array.isArray(apartmentsData.data)) {
      // Извлекаем планы из квартир (если у них есть plan или plan_image)
      apartmentsData.data.forEach(apt => {
        if (apt.plan || apt.plan_image) {
          plans.push({
            name: apt.name || apt.plan_name || 'План',
            area: apt.area,
            rooms: apt.rooms,
            image: apt.plan || apt.plan_image,
            apartment_id: apt.id || apt._id,
          })
        }
      })
    }
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
            const planImage = plan.image?.url || 
                            (plan.images && plan.images.length > 0 ? getImageUrl(plan.images[0]) : null) ||
                            (plan.image && typeof plan.image === 'string' ? plan.image : null) ||
                            (plan.plan_image ? getImageUrl(plan.plan_image) : null)
            const planName = plan.name || plan.plan_name || 'План'
            const area = plan.area || plan.area_from || plan.area_to || null
            const rooms = plan.rooms || plan.room_count || null
            
            return (
              <div key={index} className="plan-card">
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
                    {area && (
                      <div className="plan-info-item">
                        <span className="plan-info-label">Площадь:</span>
                        <span className="plan-info-value">
                          {typeof area === 'object' && area.from && area.to
                            ? `${area.from} - ${area.to} м²`
                            : `${area} м²`}
                        </span>
                      </div>
                    )}
                    {rooms && (
                      <div className="plan-info-item">
                        <span className="plan-info-label">Комнат:</span>
                        <span className="plan-info-value">{rooms}</span>
                      </div>
                    )}
                  </div>
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
