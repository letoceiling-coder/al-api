import './ObjectHeader.css'
import { getImageUrl as getImageUrlUtil, getImageUrlFull } from '../../utils/imageUtils'

const ObjectHeader = ({ objectData, advantages, buildings }) => {
  const getName = () => objectData?.name || 'Без названия'
  const getAddress = () => objectData?.address || ''
  const getDescription = () => objectData?.description || objectData?.about || ''
  const getImageUrl = (img) => {
    if (!img) return ''
    if (typeof img === 'string') return img
    if (typeof img === 'object' && img !== null) {
      // Используем утилиту для получения URL
      const imgUrl = getImageUrlUtil(img)
      const imgUrlFull = getImageUrlFull(img)
      if (imgUrl) {
        return { url: imgUrl, urlFull: imgUrlFull || imgUrl }
      }
      if (Array.isArray(img) && img.length > 0) {
        return getImageUrl(img[0])
      }
    }
    return ''
  }

  const getImages = () => {
    const images = []
    
    // Проверяем renderer
    if (objectData?.renderer && Array.isArray(objectData.renderer)) {
      objectData.renderer.forEach(img => {
        const imgUrl = getImageUrl(img)
        if (typeof imgUrl === 'string' && imgUrl) {
          images.push({ url: imgUrl, urlFull: imgUrl })
        } else if (typeof imgUrl === 'object' && imgUrl.url) {
          images.push(imgUrl)
        }
      })
    }
    
    // Проверяем images
    if (objectData?.images && Array.isArray(objectData.images)) {
      objectData.images.forEach(img => {
        const imgUrl = getImageUrl(img)
        if (typeof imgUrl === 'string' && imgUrl) {
          images.push({ url: imgUrl, urlFull: imgUrl })
        } else if (typeof imgUrl === 'object' && imgUrl.url) {
          images.push(imgUrl)
        }
      })
    }
    
    // Проверяем одиночное image
    if (objectData?.image) {
      const imgUrl = getImageUrl(objectData.image)
      if (typeof imgUrl === 'string' && imgUrl) {
        images.push({ url: imgUrl, urlFull: imgUrl })
      } else if (typeof imgUrl === 'object' && imgUrl.url) {
        images.push(imgUrl)
      }
    }
    
    return images
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
                      src={getImageUrlUtil(advantage.image)}
                      alt={advantage.name || advantage.title}
                      className="advantage-image"
                      onError={(e) => { e.target.style.display = 'none' }}
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
