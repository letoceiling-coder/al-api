import './ObjectCommerce.css'

const ObjectCommerce = ({ commerceData }) => {
  const premises = commerceData?.data || commerceData || []

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
        {premises.map((premise, index) => (
          <div key={premise._id || index} className="premise-card">
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
        ))}
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
