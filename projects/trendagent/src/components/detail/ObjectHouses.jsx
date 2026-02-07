import './ObjectHouses.css'

const ObjectHouses = ({ housesData, unifiedData }) => {
  if (!housesData && !unifiedData) {
    return null
  }

  const formatPrice = (price) => {
    return new Intl.NumberFormat('ru-RU', {
      style: 'currency',
      currency: 'RUB',
      minimumFractionDigits: 0,
    }).format(price)
  }

  const houses = housesData?.data || housesData || []
  const hasHouses = Array.isArray(houses) && houses.length > 0

  if (!hasHouses) {
    return null
  }

  return (
    <div className="object-houses">
      <h2>Дома и таунхаусы</h2>
      <div className="houses-grid">
        {houses.map((house, index) => (
          <div key={index} className="house-card">
            {house.image && (
              <div className="house-card-image">
                <img src={house.image.url || house.image} alt={house.name || 'Дом'} />
              </div>
            )}
            <div className="house-card-content">
              {house.name && <h3 className="house-card-title">{house.name}</h3>}
              {house.area && (
                <div className="house-card-info">
                  <span className="info-label">Площадь:</span>
                  <span className="info-value">{house.area} м²</span>
                </div>
              )}
              {house.price && (
                <div className="house-card-price">
                  {formatPrice(house.price)}
                </div>
              )}
            </div>
          </div>
        ))}
      </div>
    </div>
  )
}

export default ObjectHouses
