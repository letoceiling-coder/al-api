import { Link } from 'react-router-dom'
import './ObjectCard.css'

const ObjectCard = ({ object, objectType, onClick }) => {
  const getObjectId = () => {
    return object._id || object.id || object.guid
  }

  const getObjectGuid = () => {
    return object.guid || null
  }

  const getName = () => {
    return object.name || 'Без названия'
  }

  const getAddress = () => {
    return object.address || ''
  }

  const getImage = () => {
    if (object.image) {
      return object.image.url_full || object.image.url || null
    }
    return null
  }

  const getPrice = () => {
    if (object.min_price && object.max_price) {
      if (object.min_price === object.max_price) {
        return formatPrice(object.min_price)
      }
      return `${formatPrice(object.min_price)} - ${formatPrice(object.max_price)}`
    }
    if (object.min_price) {
      return `от ${formatPrice(object.min_price)}`
    }
    if (object.price) {
      return formatPrice(object.price)
    }
    return 'Цена не указана'
  }

  const getCount = () => {
    if (objectType === 'apartments') {
      return object.apart_count || 0
    }
    if (objectType === 'parkings') {
      return object.places_count || 0
    }
    return null
  }

  const getCountLabel = () => {
    if (objectType === 'apartments') {
      return 'квартир'
    }
    if (objectType === 'parkings') {
      return 'мест'
    }
    return ''
  }

  const getDeadline = () => {
    if (object.deadline) {
      const date = new Date(object.deadline)
      return date.toLocaleDateString('ru-RU', { year: 'numeric', month: 'long' })
    }
    return null
  }

  const formatPrice = (price) => {
    if (!price || price === 0) return '0 ₽'
    return new Intl.NumberFormat('ru-RU', {
      style: 'currency',
      currency: 'RUB',
      minimumFractionDigits: 0,
    }).format(price)
  }

  const handleImageError = (e) => {
    e.target.style.display = 'none'
  }

  const objectId = getObjectId()
  const objectGuid = getObjectGuid()
  const linkTo = `/${objectType}/${objectId}${objectGuid ? `?guid=${objectGuid}` : ''}`

  return (
    <Link to={linkTo} className="object-card" onClick={onClick}>
      {/* Изображение */}
      <div className="object-card-image">
        {getImage() ? (
          <img
            src={getImage()}
            alt={getName()}
            onError={handleImageError}
          />
        ) : (
          <div className="object-card-image-placeholder">
            <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z" />
            </svg>
          </div>
        )}
      </div>

      {/* Контент */}
      <div className="object-card-content">
        <h3 className="object-card-title">{getName()}</h3>
        {getAddress() && (
          <p className="object-card-address">{getAddress()}</p>
        )}

        <div className="object-card-info">
          {getDeadline() && (
            <div className="info-item">
              <span className="info-label">Срок сдачи:</span>
              <span>{getDeadline()}</span>
            </div>
          )}
        </div>

        <div className="object-card-footer">
          <div className="object-card-price">{getPrice()}</div>
          {getCount() !== null && (
            <div className="object-card-count">
              {getCount()} {getCountLabel()}
            </div>
          )}
        </div>
      </div>
    </Link>
  )
}

export default ObjectCard
