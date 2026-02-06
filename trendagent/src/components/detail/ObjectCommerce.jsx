import { getImageUrl } from '../../utils/imageUtils'
import './ObjectCommerce.css'

const ObjectCommerce = ({ commerceData }) => {
  const premises = Array.isArray(commerceData?.data) 
    ? commerceData.data 
    : (Array.isArray(commerceData) ? commerceData : [])

  if (!premises || premises.length === 0) {
    return (
      <div className="object-commerce card">
        <h2 className="section-title">Коммерческая недвижимость</h2>
        <div className="empty-state">
          <p>Информация о коммерческой недвижимости отсутствует</p>
        </div>
      </div>
    )
  }

  return (
    <div className="object-commerce card">
      <h2 className="section-title">Коммерческая недвижимость</h2>
      <div className="commerce-grid">
        {premises.map((premise, index) => {
          const imageUrl = getImageUrl(premise.image || premise.images?.[0])
          return (
            <div key={premise._id || index} className="premise-card">
              {imageUrl && (
                <div className="premise-image">
                  <img src={imageUrl} alt={premise.name || `Помещение ${index + 1}`} />
                </div>
              )}
              <div className="premise-content">
                <h3 className="premise-title">{premise.name || `Помещение ${index + 1}`}</h3>
                {premise.area && (
                  <div className="premise-info">
                    <span className="info-label">Площадь:</span>
                    <span className="info-value">{premise.area} м²</span>
                  </div>
                )}
                {premise.price && (
                  <div className="premise-price">
                    {formatPrice(premise.price)}
                  </div>
                )}
              </div>
            </div>
          )
        })}
      </div>
    </div>
  )
}

const formatPrice = (price) => {
  return new Intl.NumberFormat('ru-RU', {
    style: 'currency',
    currency: 'RUB',
    minimumFractionDigits: 0,
  }).format(price)
}

export default ObjectCommerce
