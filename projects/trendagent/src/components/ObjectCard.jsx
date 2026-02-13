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
      const imageUrl = getImageUrl(firstImage)
      if (imageUrl) return imageUrl
    }
    
    // Затем проверяем одиночное поле image (для квартир и других типов)
    if (object.image) {
      // Если image - это объект с уже готовым URL
      if (typeof object.image === 'object' && object.image !== null) {
        // Проверяем готовые URL поля (для комплексов)
        if (object.image.url) {
          return object.image.url
        }
        if (object.image.thumbnail) {
          return object.image.thumbnail
        }
        if (object.image.url_full) {
          return object.image.url_full
        }
        if (object.image.full) {
          return object.image.full
        }
      }
      // Пробуем через imageUtils
      const imageUrl = getImageUrl(object.image)
      if (imageUrl) return imageUrl
    }
    
    // Проверяем renderer (для некоторых типов объектов)
    if (object.renderer && Array.isArray(object.renderer) && object.renderer.length > 0) {
      const imageUrl = getImageUrl(object.renderer[0])
      if (imageUrl) return imageUrl
    }
    
    // Проверяем дополнительные поля для изображений
    if (object.photo) {
      const imageUrl = getImageUrl(object.photo)
      if (imageUrl) return imageUrl
    }
    
    if (object.photo_url) {
      return object.photo_url
    }
    
    if (object.image_url) {
      return object.image_url
    }
    
    // Проверяем gallery (для блоков/комплексов)
    if (object.gallery && Array.isArray(object.gallery) && object.gallery.length > 0) {
      const imageUrl = getImageUrl(object.gallery[0])
      if (imageUrl) return imageUrl
    }
    
    // Проверяем медиа-поля (для блоков)
    if (object.media && Array.isArray(object.media) && object.media.length > 0) {
      const imageUrl = getImageUrl(object.media[0])
      if (imageUrl) return imageUrl
    }
    
    // Проверяем preview_image
    if (object.preview_image) {
      const imageUrl = getImageUrl(object.preview_image)
      if (imageUrl) return imageUrl
    }
    
    // Проверяем cover_image
    if (object.cover_image) {
      const imageUrl = getImageUrl(object.cover_image)
      if (imageUrl) return imageUrl
    }
    
    // Проверяем main_image
    if (object.main_image) {
      const imageUrl = getImageUrl(object.main_image)
      if (imageUrl) return imageUrl
    }
    
    // Логируем для отладки, если изображение не найдено
    const hasAnyImage = !!(object.images || object.image || object.renderer || object.photo || object.photo_url || object.image_url || object.gallery || object.plan || (objectType === 'apartments' && (object.block_image || (object.block && object.block.image))))
    if (!hasAnyImage) {
      // Собираем все ключи, которые могут содержать изображения
      const imageRelatedKeys = Object.keys(object).filter(key => 
        key.toLowerCase().includes('image') || 
        key.toLowerCase().includes('photo') || 
        key.toLowerCase().includes('picture') || 
        key.toLowerCase().includes('plan') ||
        key.toLowerCase().includes('gallery') ||
        key.toLowerCase().includes('media') ||
        key.toLowerCase().includes('renderer') ||
        key.toLowerCase().includes('preview') ||
        key.toLowerCase().includes('cover') ||
        key.toLowerCase().includes('thumbnail')
      )
      
      console.log('ObjectCard: изображение не найдено для объекта:', {
        id: object._id || object.id,
        name: object.name || object.title || object.block_name,
        allKeys: Object.keys(object),
        imageRelatedKeys: imageRelatedKeys,
        objectType,
        // Выводим все поля, которые могут содержать изображения
        has_images: !!object.images,
        has_image: !!object.image,
        has_renderer: !!object.renderer,
        has_photo: !!object.photo,
        has_photo_url: !!object.photo_url,
        has_image_url: !!object.image_url,
        has_gallery: !!object.gallery,
        has_media: !!object.media,
        has_preview_image: !!object.preview_image,
        has_cover_image: !!object.cover_image,
        has_main_image: !!object.main_image,
        has_plan: !!object.plan,
        has_block_image: !!object.block_image,
        has_block: !!object.block,
        has_block_image_nested: !!(object.block && object.block.image),
        // Выводим структуру image, если есть
        image_structure: object.image || null,
        // Выводим первый элемент images, если есть
        first_image_structure: (object.images && Array.isArray(object.images) && object.images.length > 0) ? object.images[0] : null,
        // Выводим plan, если есть (для квартир)
        plan_structure: object.plan || null,
        // Выводим значения всех ключей, связанных с изображениями
        imageRelatedValues: imageRelatedKeys.reduce((acc, key) => {
          acc[key] = object[key]
          return acc
        }, {}),
      })
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
        // Для домов может быть price или value (числовое значение)
        const priceValue = firstPrice.price || firstPrice.value || firstPrice.unformatted_value
        if (priceValue && priceValue > 0) {
          const unit = firstPrice.unit || '₽'
          // Для домов всегда показываем "от" если нет label или label пустой
          const label = (firstPrice.label && firstPrice.label.trim()) ? firstPrice.label : 'от'
          // Используем formatted_value если есть, иначе форматируем сами
          if (firstPrice.formatted_value) {
            return `${label} ${firstPrice.formatted_value} ${unit}`.trim()
          }
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
