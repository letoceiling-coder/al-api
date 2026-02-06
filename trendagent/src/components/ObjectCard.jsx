import { Link } from 'react-router-dom'
import { getImageUrl } from '../utils/imageUtils'
import './ObjectCard.css'

const ObjectCard = ({ object, objectType, onClick }) => {
  // Отладка для участков
  if (objectType === 'plots' && !object.name) {
    console.log('ObjectCard plots debug:', {
      object,
      hasName: !!object.name,
      hasVillageName: !!object.village_name,
      hasMinPrices: !!object.min_prices,
      minPricesLength: object.min_prices?.length || 0,
      hasImages: !!object.images,
      imagesLength: object.images?.length || 0,
    })
  }

  const getObjectId = () => {
    // Приоритет: _id, затем id, но не guid (guid используется отдельно)
    return object._id || object.id || null
  }

  const getObjectGuid = () => {
    return object.guid || null
  }

  const getName = () => {
    // Для участков может быть название в разных полях
    if (objectType === 'plots') {
      // Проверяем все возможные варианты названия
      return object.name || 
             object.village_name || 
             object.village?.name ||
             object.block_name ||
             'Без названия'
    }
    return object.name || 'Без названия'
  }

  const getAddress = () => {
    return object.address || ''
  }

  const getImage = () => {
    // Сначала проверяем массив images (для паркингов, домов, участков, коммерции)
    if (object.images && Array.isArray(object.images) && object.images.length > 0) {
      const firstImage = object.images[0]
      
      // Для участков images может быть массивом объектов с thumbnail/full
      if (firstImage.thumbnail) {
        return firstImage.thumbnail
      }
      if (firstImage.full) {
        return firstImage.full
      }
      
      // Если есть path и file_name, формируем URL
      if (firstImage.path && firstImage.file_name) {
        const path = firstImage.path.replace(/^\/+|\/+$/g, '')
        const fileName = firstImage.file_name
        return `https://selcdn.trendagent.ru/images/${path}/m_${fileName}`
      }
      
      // Пробуем через imageUtils
      return getImageUrl(firstImage)
    }
    
    // Затем проверяем одиночное поле image (для квартир и других типов)
    if (object.image) {
      return getImageUrl(object.image)
    }
    
    // Проверяем renderer (для некоторых типов объектов)
    if (object.renderer && Array.isArray(object.renderer) && object.renderer.length > 0) {
      return getImageUrl(object.renderer[0])
    }
    
    return null
  }

  const getPrice = () => {
    // Для участков цена хранится в массиве min_prices
    if (objectType === 'plots') {
      // Проверяем массив min_prices
      if (object.min_prices && Array.isArray(object.min_prices) && object.min_prices.length > 0) {
        const firstPrice = object.min_prices[0]
        if (firstPrice && firstPrice.value) {
          const unit = firstPrice.unit || '₽'
          const label = firstPrice.label ? `${firstPrice.label}: ` : ''
          return `${label}${formatPrice(firstPrice.value)} ${unit}`
        }
      }
      // Проверяем альтернативные варианты для участков
      if (object.min_price) {
        return `от ${formatPrice(object.min_price)}`
      }
      if (object.price) {
        return formatPrice(object.price)
      }
      return 'Цена не указана'
    }
    
    // Стандартная обработка для других типов
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
  // Формируем URL: если есть ID, используем его, иначе используем GUID
  // Если есть и ID и GUID, используем ID в пути, а GUID в query параметрах
  const identifier = objectId || objectGuid
  const linkTo = identifier ? `/${objectType}/${identifier}${objectId && objectGuid ? `?guid=${objectGuid}` : ''}` : '#'

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
