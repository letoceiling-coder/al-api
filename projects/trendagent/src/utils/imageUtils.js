/**
 * Утилита для обработки URL изображений TrendAgent
 * Поддерживает различные форматы данных изображений из API
 */

export const getImageUrl = (img) => {
  if (!img) return ''
  
  // Если это строка (URL или путь)
  if (typeof img === 'string') {
    // Если это уже полный URL, возвращаем
    if (img.startsWith('http://') || img.startsWith('https://')) {
      return img
    }
    // Если это путь, формируем URL
    if (img.startsWith('/')) {
      return `https://selcdn.trendagent.ru${img}`
    }
    // Если это просто имя файла, формируем базовый URL
    return `https://selcdn.trendagent.ru/images/${img}`
  }
  
  // Если это объект
  if (typeof img === 'object' && img !== null) {
    // Если это массив, берем первый элемент
    if (Array.isArray(img) && img.length > 0) {
      return getImageUrl(img[0])
    }
    
    // Извлекаем URL из различных полей объекта
    const url = img.url || 
                img.url_full || 
                img.url_medium ||
                img.url_small ||
                img.src || 
                img.image || 
                img.large || 
                img.medium || 
                img.small || 
                img.value || 
                img.original || 
                img.plan ||
                img.thumbnail ||
                img.full ||
                img.preview ||
                img.cover
    
    if (url && typeof url === 'string') {
      // Если это уже полный URL, возвращаем
      if (url.startsWith('http://') || url.startsWith('https://')) {
        return url
      }
      // Если это путь, формируем URL
      if (url.startsWith('/')) {
        return `https://selcdn.trendagent.ru${url}`
      }
      // Если это просто имя файла, формируем базовый URL
      return `https://selcdn.trendagent.ru/images/${url}`
    }
    
    // Формируем URL из path и file_name
    if (img.path && img.file_name) {
      const path = img.path.replace(/^\/+|\/+$/g, '')
      const fileName = img.file_name
      // Для миниатюры используем префикс m_
      return `https://selcdn.trendagent.ru/images/${path}/m_${fileName}`
    }
    
    // Проверяем, есть ли вложенный объект image
    if (img.image && typeof img.image === 'object') {
      return getImageUrl(img.image)
    }
  }
  
  return ''
}

/**
 * Получает полный URL изображения (без префикса m_)
 */
export const getImageUrlFull = (img) => {
  if (!img) return ''
  
  // Если это строка
  if (typeof img === 'string') {
    if (img.startsWith('http://') || img.startsWith('https://')) {
      return img
    }
    if (img.startsWith('/')) {
      return `https://selcdn.trendagent.ru${img}`
    }
    return `https://selcdn.trendagent.ru/images/${img}`
  }
  
  // Если это объект
  if (typeof img === 'object' && img !== null) {
    // Сначала пробуем получить полный URL
    const urlFull = img.url_full || img.url || img.src || img.large || img.original
    
    if (urlFull && typeof urlFull === 'string') {
      return urlFull
    }
    
    // Формируем URL из path и file_name (без префикса m_)
    if (img.path && img.file_name) {
      const path = img.path.replace(/^\/+|\/+$/g, '')
      const fileName = img.file_name
      return `https://selcdn.trendagent.ru/images/${path}/${fileName}`
    }
    
    if (Array.isArray(img) && img.length > 0) {
      return getImageUrlFull(img[0])
    }
  }
  
  return ''
}

/**
 * Получает объект с миниатюрой и полным URL
 */
export const getImageUrls = (img) => {
  return {
    url: getImageUrl(img),
    urlFull: getImageUrlFull(img)
  }
}
