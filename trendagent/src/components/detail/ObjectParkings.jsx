import './ObjectParkings.css'

const ObjectParkings = ({ parkingsData }) => {
  const parkings = parkingsData?.data || parkingsData || []

  if (!parkings || parkings.length === 0) {
    return (
      <div className="object-parkings card">
        <h2 className="section-title">Паркинги</h2>
        <div className="empty-state">
          <p>Информация о паркингах отсутствует</p>
        </div>
      </div>
    )
  }

  return (
    <div className="object-parkings card">
      <h2 className="section-title">Паркинги</h2>
      <div className="parkings-list">
        {parkings.map((parking, index) => (
          <div key={parking._id || index} className="parking-item">
            <div className="parking-info">
              <h3 className="parking-title">{parking.name || `Паркинг ${index + 1}`}</h3>
              {parking.description && (
                <p className="parking-description">{parking.description}</p>
              )}
              {parking.price && (
                <div className="parking-price">
                  {formatPrice(parking.price)}
                </div>
              )}
            </div>
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

export default ObjectParkings
