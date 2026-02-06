import './ObjectHeader.css'

const ObjectHeader = ({ objectData, advantages, buildings }) => {
  const getName = () => objectData?.name || 'Без названия'
  const getAddress = () => objectData?.address || ''
  const getDescription = () => objectData?.description || objectData?.about || ''
  const getImages = () => {
    if (objectData?.renderer && Array.isArray(objectData.renderer)) {
      return objectData.renderer.map(img => ({
        url: img.url || `https://selcdn.trendagent.ru/images/${img.path || ''}m_${img.file_name || ''}`,
        urlFull: img.url_full || `https://selcdn.trendagent.ru/images/${img.path || ''}${img.file_name || ''}`,
      }))
    }
    return []
  }

  const getMinPrice = () => {
    if (objectData?.min_price) {
      return formatPrice(objectData.min_price)
    }
    return null
  }

  const getDeadline = () => {
    if (objectData?.deadline) {
      const date = new Date(objectData.deadline)
      return date.toLocaleDateString('ru-RU', { year: 'numeric', month: 'long', day: 'numeric' })
    }
    return null
  }

  const formatPrice = (price) => {
    return new Intl.NumberFormat('ru-RU', {
      style: 'currency',
      currency: 'RUB',
      minimumFractionDigits: 0,
    }).format(price)
  }

  const images = getImages()

  return (
    <div className="object-header card">
      <div className="header-content">
        <h1 className="object-title">{getName()}</h1>
        {getAddress() && (
          <p className="object-address">{getAddress()}</p>
        )}

        <div className="header-info">
          {getMinPrice() && (
            <div className="info-item">
              <span className="info-label">Цена от:</span>
              <span className="info-value price">{getMinPrice()}</span>
            </div>
          )}
          {getDeadline() && (
            <div className="info-item">
              <span className="info-label">Срок сдачи:</span>
              <span className="info-value">{getDeadline()}</span>
            </div>
          )}
        </div>

        {getDescription() && (
          <div className="object-description" dangerouslySetInnerHTML={{ __html: getDescription() }} />
        )}

        {/* Галерея изображений */}
        {images.length > 0 && (
          <div className="object-gallery">
            <div className="gallery-main">
              {images[0] && (
                <img
                  src={images[0].urlFull || images[0].url}
                  alt={getName()}
                  className="gallery-main-image"
                />
              )}
            </div>
            {images.length > 1 && (
              <div className="gallery-thumbnails">
                {images.slice(1, 5).map((img, index) => (
                  <img
                    key={index}
                    src={img.url}
                    alt={`${getName()} - ${index + 2}`}
                    className="gallery-thumbnail"
                  />
                ))}
              </div>
            )}
          </div>
        )}

        {/* Преимущества */}
        {advantages && advantages.length > 0 && (
          <div className="object-advantages">
            <h3 className="section-title">Преимущества</h3>
            <div className="advantages-grid">
              {advantages.map((advantage, index) => (
                <div key={index} className="advantage-item">
                  {advantage.image && (
                    <img
                      src={advantage.image.url || advantage.image}
                      alt={advantage.name || advantage.title}
                      className="advantage-image"
                    />
                  )}
                  <div className="advantage-content">
                    <h4 className="advantage-title">{advantage.name || advantage.title}</h4>
                    {advantage.description && (
                      <p className="advantage-description">{advantage.description}</p>
                    )}
                  </div>
                </div>
              ))}
            </div>
          </div>
        )}
      </div>
    </div>
  )
}

export default ObjectHeader
