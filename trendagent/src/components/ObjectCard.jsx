import { Link } from 'react-router-dom'
import { getImageUrl } from '../utils/imageUtils'
import './ObjectCard.css'

const ObjectCard = ({ object, objectType, onClick }) => {
  // Отладка для участков - выводим полную структуру первого объекта
  if (objectType === 'plots' && !object.name) {
    console.log('ObjectCard plots debug - FULL OBJECT:', JSON.stringify(object, null, 2))
    console.log('ObjectCard plots debug - KEYS:', Object.keys(object))
  }

  const getObjectId = () => {
    // Приоритет: _id, затем id, но не guid (guid используется отдельно)
    return object._id || object.id || null
  }

  const getObjectGuid = () => {
    return object.guid || null
  }

  const getName = () => {
    // Для участков может быть название в разных полях (как в старом проекте)
    if (objectType === 'plots') {
      return object.name || 
             object.title || 
             object.village_name || 
             object.village?.name ||
             object.block_name ||
             'Без названия'
    }
    return object.name || object.title || object.block_name || 'Без названия'
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
    // Обработка цены как в старом проекте (ObjectCard.vue)
    if (object.price) {
      if (typeof object.price === 'number') {
        return formatPrice(object.price)
      }
      if (object.price.min) {
        return `от ${formatPrice(object.price.min)}`
      }
      if (object.price.value) {
        return formatPrice(object.price.value)
      }
    }
    
    // Для участков и домов цена может быть в массиве min_prices
    if (objectType === 'plots' || objectType === 'houses') {
      // Проверяем массив min_prices
      if (object.min_prices && Array.isArray(object.min_prices) && object.min_prices.length > 0) {
        const firstPrice = object.min_prices[0]
        // Для домов может быть price или value
        const priceValue = firstPrice.price || firstPrice.value
        if (priceValue && priceValue > 0) {
          const unit = firstPrice.unit || '₽'
          // Для домов всегда показываем "от" если нет label
          const label = firstPrice.label || 'от'
          return `${label} ${formatPrice(priceValue)} ${unit}`.trim()
        }
      }
      // Проверяем альтернативные варианты
      if (object.min_price && object.min_price > 0) {
        return `от ${formatPrice(object.min_price)}`
      }
      if (object.price_from && object.price_from > 0) {
        return `от ${formatPrice(object.price_from)}`
      }
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
    if (object.price_from) {
      return `от ${formatPrice(object.price_from)}`
    }
    return null
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
    if (!object.deadline) {
      return null
    }
    
    // Если deadline - массив (для коммерции и домов)
    if (Array.isArray(object.deadline) && object.deadline.length > 0) {
      const firstDeadline = object.deadline[0]
      const deadlineValue = firstDeadline.deadline || firstDeadline
      if (deadlineValue) {
        try {
          const date = new Date(deadlineValue)
          if (!isNaN(date.getTime())) {
            return date.toLocaleDateString('ru-RU', { year: 'numeric', month: 'long' })
          }
        } catch (e) {
          console.warn('Invalid deadline date:', deadlineValue)
        }
      }
      return null
    }
    
    // Если deadline - строка или число
    if (typeof object.deadline === 'string' || typeof object.deadline === 'number') {
      try {
        const date = new Date(object.deadline)
        if (!isNaN(date.getTime())) {
          return date.toLocaleDateString('ru-RU', { year: 'numeric', month: 'long' })
        }
      } catch (e) {
        console.warn('Invalid deadline date:', object.deadline)
      }
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
          {getPrice() ? (
            <div className="object-card-price">{getPrice()}</div>
          ) : (
            <div className="object-card-price">Цена не указана</div>
          )}
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
