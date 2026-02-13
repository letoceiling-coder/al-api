import { useState, useEffect, useRef } from 'react'
import { useNavigate, useSearchParams } from 'react-router-dom'
import { trendAgentAPI } from '../services/api'
import './ObjectsMap.css'

const ObjectsMap = () => {
  const navigate = useNavigate()
  const [searchParams] = useSearchParams()
  const [authData, setAuthData] = useState(null)
  const [objects, setObjects] = useState([])
  const [loading, setLoading] = useState(false)
  const [selectedBlock, setSelectedBlock] = useState(null)
  const [blockData, setBlockData] = useState(null)
  const [blockGallery, setBlockGallery] = useState(null)
  const [loadingBlock, setLoadingBlock] = useState(false)
  const [filters, setFilters] = useState({
    city: '58c665588b6aa52311afa01b',
    phone: '+79045393434',
    password: 'nwBvh4q',
    room: searchParams.getAll('room').map(r => parseInt(r)).filter(Boolean),
  })

  useEffect(() => {
    authenticate()
  }, [])

  useEffect(() => {
    if (authData && authData.authenticated) {
      loadObjects()
    }
  }, [authData, filters])

  const authenticate = async () => {
    try {
      const response = await trendAgentAPI.authenticate(
        filters.phone,
        filters.password
      )
      if (response.success && response.data.authenticated) {
        setAuthData(response.data)
      }
    } catch (error) {
      console.error('Ошибка авторизации:', error)
    }
  }

  const loadObjects = async () => {
    if (!authData || !authData.authenticated) return

    setLoading(true)
    try {
      const params = {
        phone: filters.phone,
        password: filters.password,
        city: filters.city,
        count: 1000, // Загружаем больше для карты
        offset: 0,
        room: filters.room,
      }

      // Для карты используем блоки (комплексы) с координатами
      // Используем getApartments с параметром show_type=map для получения блоков
      try {
        const blocksResponse = await trendAgentAPI.getApartments({
          ...params,
          show_type: 'map', // Специальный параметр для карты - возвращает блоки с координатами
        })
        
        if (blocksResponse.success) {
          // Для карты API возвращает блоки с координатами
          const blocksList = blocksResponse.data?.objects || blocksResponse.data?.data || []
          console.log('Загружено блоков для карты:', blocksList.length)
          
          // Логируем структуру первого блока для отладки
          if (blocksList.length > 0) {
            console.log('Пример блока:', blocksList[0])
            const firstCoord = getCoordinates(blocksList[0])
            console.log('Координаты первого блока:', firstCoord)
            // Логируем все ключи объекта для отладки
            console.log('Ключи блока:', Object.keys(blocksList[0]))
          }
          
          setObjects(blocksList)
          return
        }
      } catch (error) {
        console.error('Ошибка загрузки блоков для карты:', error)
      }
      
      // Fallback: используем квартиры (но координат может не быть)
      const response = await trendAgentAPI.getApartments(params)
      if (response.success) {
        const objectsList = response.data?.objects || response.data?.data || []
        console.log('Загружено объектов (fallback):', objectsList.length)
        
        if (objectsList.length > 0) {
          console.log('Пример объекта:', objectsList[0])
          const firstCoord = getCoordinates(objectsList[0])
          console.log('Координаты первого объекта:', firstCoord)
        }
        
        setObjects(objectsList)
      }
    } catch (error) {
      console.error('Ошибка загрузки объектов:', error)
      setObjects([])
    } finally {
      setLoading(false)
    }
  }

  // Получаем минимальную цену из блока
  const getMinPrice = (block) => {
    // Проверяем apartmentsMinPrices (массив объектов с room и price)
    if (block.apartmentsMinPrices && Array.isArray(block.apartmentsMinPrices) && block.apartmentsMinPrices.length > 0) {
      const prices = block.apartmentsMinPrices
        .map(p => {
          const priceStr = p.price || p.price_value || ''
          // Убираем пробелы и извлекаем число
          const priceNum = parseFloat(priceStr.replace(/\s/g, ''))
          return isNaN(priceNum) ? null : priceNum
        })
        .filter(p => p !== null)
      
      if (prices.length > 0) {
        return Math.min(...prices)
      }
    }
    
    // Проверяем min_price
    if (block.min_price) {
      const priceNum = typeof block.min_price === 'number' ? block.min_price : parseFloat(block.min_price)
      if (!isNaN(priceNum)) {
        return priceNum
      }
    }
    
    // Проверяем min_prices (массив)
    if (block.min_prices && Array.isArray(block.min_prices) && block.min_prices.length > 0) {
      const prices = block.min_prices
        .map(p => {
          const price = p.price || p.price_value || p.value || ''
          const priceNum = parseFloat(price.toString().replace(/\s/g, ''))
          return isNaN(priceNum) ? null : priceNum
        })
        .filter(p => p !== null)
      
      if (prices.length > 0) {
        return Math.min(...prices)
      }
    }
    
    return null
  }

  // Форматируем цену для отображения
  const formatPriceForMarker = (price) => {
    if (!price) return null
    const priceInMillions = price / 1000000
    if (priceInMillions >= 1) {
      return `${priceInMillions.toFixed(1)} млн`
    }
    return `${Math.round(price / 1000)} тыс`
  }

  // Загрузка данных блока при клике на маркер
  const loadBlockData = async (blockId) => {
    if (!authData || !authData.authenticated) return

    setLoadingBlock(true)
    try {
      const params = {
        phone: filters.phone,
        password: filters.password,
        formating: 'true',
        reservation: 'true',
      }

      const [mapResponse, galleryResponse] = await Promise.all([
        trendAgentAPI.getBlockMap(blockId, params),
        trendAgentAPI.getBlockGallery(blockId, params).catch(() => ({ success: false, data: null }))
      ])

      if (mapResponse.success) {
        setBlockData(mapResponse.data)
      }
      if (galleryResponse.success && galleryResponse.data) {
        setBlockGallery(galleryResponse.data)
      }
    } catch (error) {
      console.error('Ошибка загрузки данных блока:', error)
    } finally {
      setLoadingBlock(false)
    }
  }

  // Получаем координаты из объекта
  const getCoordinates = (obj) => {
    // Проверяем разные возможные структуры координат
    // Формат [lat, lon] или [lon, lat]
    if (obj.coordinates) {
      if (Array.isArray(obj.coordinates) && obj.coordinates.length >= 2) {
        // Предполагаем формат [lat, lon] для массива
        return { lat: obj.coordinates[0], lon: obj.coordinates[1] }
      }
      if (obj.coordinates.lat && obj.coordinates.lon) {
        return { lat: obj.coordinates.lat, lon: obj.coordinates.lon }
      }
      if (obj.coordinates.latitude && obj.coordinates.longitude) {
        return { lat: obj.coordinates.latitude, lon: obj.coordinates.longitude }
      }
    }
    
    // Проверяем geo.coordinates
    if (obj.geo && obj.geo.coordinates) {
      if (Array.isArray(obj.geo.coordinates) && obj.geo.coordinates.length >= 2) {
        // Формат GeoJSON: [lon, lat]
        return { lat: obj.geo.coordinates[1], lon: obj.geo.coordinates[0] }
      }
      if (obj.geo.coordinates.lat && obj.geo.coordinates.lon) {
        return { lat: obj.geo.coordinates.lat, lon: obj.geo.coordinates.lon }
      }
    }
    
    // Проверяем location
    if (obj.location) {
      if (obj.location.coordinates && Array.isArray(obj.location.coordinates) && obj.location.coordinates.length >= 2) {
        // Формат GeoJSON: [lon, lat]
        return { lat: obj.location.coordinates[1], lon: obj.location.coordinates[0] }
      }
      if (obj.location.lat && obj.location.lon) {
        return { lat: obj.location.lat, lon: obj.location.lon }
      }
      if (obj.location.latitude && obj.location.longitude) {
        return { lat: obj.location.latitude, lon: obj.location.longitude }
      }
    }
    
    // Прямые поля (приоритет для latitude/longitude, так как они есть в блоках)
    if (obj.latitude && obj.longitude) {
      return { lat: parseFloat(obj.latitude), lon: parseFloat(obj.longitude) }
    }
    if (obj.lat && obj.lon) {
      return { lat: parseFloat(obj.lat), lon: parseFloat(obj.lon) }
    }
    
    return null
  }

  // API ключ Яндекс.Карт
  const YANDEX_MAPS_API_KEY = 'a79c56f4-efea-471e-bee5-fe9226cd53fd'
  const mapContainerRef = useRef(null)
  const mapInstanceRef = useRef(null)
  const markersRef = useRef([])

  // Инициализация Яндекс.Карт
  useEffect(() => {
    // Ждем, пока загрузка завершится и контейнер будет готов
    if (loading || !mapContainerRef.current || objects.length === 0) {
      console.log('Инициализация карты: ожидание готовности', {
        loading,
        hasContainer: !!mapContainerRef.current,
        objectsCount: objects.length
      })
      return
    }

    console.log('Начало инициализации карты, объектов:', objects.length, 'контейнер:', mapContainerRef.current)

    // Загружаем скрипт Яндекс.Карт, если еще не загружен
    if (!window.ymaps) {
      console.log('Загрузка скрипта Яндекс.Карт...')
      const script = document.createElement('script')
      script.src = `https://api-maps.yandex.ru/2.1/?apikey=${YANDEX_MAPS_API_KEY}&lang=ru_RU`
      script.async = true
      script.onload = () => {
        console.log('Скрипт Яндекс.Карт загружен')
        if (window.ymaps) {
          window.ymaps.ready(() => {
            console.log('ymaps.ready вызван')
            // Проверяем контейнер еще раз перед инициализацией
            if (mapContainerRef.current && objects.length > 0) {
              initMap()
            } else {
              console.error('Контейнер или объекты недоступны после загрузки ymaps')
            }
          })
        } else {
          console.error('window.ymaps не доступен после загрузки скрипта')
        }
      }
      script.onerror = () => {
        console.error('Ошибка загрузки скрипта Яндекс.Карт')
      }
      document.head.appendChild(script)
    } else {
      console.log('ymaps уже загружен, вызываем initMap')
      window.ymaps.ready(() => {
        console.log('ymaps.ready вызван (уже загружен)')
        // Проверяем контейнер еще раз перед инициализацией
        if (mapContainerRef.current && objects.length > 0) {
          initMap()
        } else {
          console.error('Контейнер или объекты недоступны после ymaps.ready')
        }
      })
    }

    return () => {
      // Очищаем карту при размонтировании
      if (clustererRef.current) {
        clustererRef.current.removeAll()
        clustererRef.current = null
      }
      if (mapInstanceRef.current) {
        mapInstanceRef.current.destroy()
        mapInstanceRef.current = null
      }
      markersRef.current = []
    }
  }, [objects, loading])

  const initMap = () => {
    if (!mapContainerRef.current) {
      console.error('initMap: mapContainerRef отсутствует')
      return
    }
    
    if (!window.ymaps) {
      console.error('initMap: window.ymaps отсутствует')
      return
    }

    console.log('Инициализация карты, объектов:', objects.length, 'контейнер готов:', !!mapContainerRef.current)

    // Уничтожаем предыдущую карту, если есть
    if (clustererRef.current) {
      clustererRef.current.removeAll()
      clustererRef.current = null
    }
    if (mapInstanceRef.current) {
      mapInstanceRef.current.destroy()
      markersRef.current = []
    }

    // Если объекты - это уже блоки (комплексы), используем их напрямую
    // Если объекты - это квартиры, группируем по блокам
    let blocksWithCoords = []
    
    if (objects.length > 0 && (objects[0]._id || objects[0].id) && !objects[0].block_id) {
      // Это блоки (комплексы), используем их напрямую
      blocksWithCoords = objects
        .map(obj => {
          const coord = getCoordinates(obj)
          if (!coord) return null
          
          const minPrice = getMinPrice(obj)
          const priceText = formatPriceForMarker(minPrice)
          
          return {
            blockId: obj._id || obj.id,
            blockGuid: obj.guid,
            blockName: obj.name || obj.title || 'Без названия',
            coord,
            apartmentsCount: obj.places_count || obj.apartments_count || 0,
            minPrice,
            priceText,
            blockData: obj // Сохраняем исходные данные блока
          }
        })
        .filter(Boolean)
    } else {
      // Это квартиры, группируем по блокам
      const blocksMap = new Map()
      
      objects.forEach(obj => {
        const blockId = obj.block_id || obj._id || obj.id
        if (!blockId) return
        
        if (!blocksMap.has(blockId)) {
          blocksMap.set(blockId, {
            blockId,
            blockGuid: obj.guid || obj.block_guid,
            blockName: obj.block_name || obj.name || obj.title || 'Без названия',
            apartments: [],
            coord: null
          })
        }
        
        blocksMap.get(blockId).apartments.push(obj)
        
        // Если координаты есть в объекте, сохраняем их
        const coord = getCoordinates(obj)
        if (coord && !blocksMap.get(blockId).coord) {
          blocksMap.get(blockId).coord = coord
        }
      })

      // Получаем координаты для каждого блока
      blocksWithCoords = Array.from(blocksMap.values())
        .map(block => {
          // Если координаты не найдены в квартирах, проверяем другие поля блока
          if (!block.coord) {
            // Проверяем geometry (может быть в блоке)
            const firstApt = block.apartments[0]
            if (firstApt?.geometry) {
              if (Array.isArray(firstApt.geometry) && firstApt.geometry.length >= 2) {
                block.coord = { lat: firstApt.geometry[0], lon: firstApt.geometry[1] }
              } else if (firstApt.geometry.lat && firstApt.geometry.lon) {
                block.coord = { lat: firstApt.geometry.lat, lon: firstApt.geometry.lon }
              }
            }
          }
          return {
            ...block,
            apartmentsCount: block.apartments.length
          }
        })
        .filter(block => block.coord !== null)
    }

    console.log('Блоков с координатами:', blocksWithCoords.length)
    if (blocksWithCoords.length === 0) {
      console.log('Нет координат. Пример объекта:', objects[0])
    }

    if (blocksWithCoords.length === 0) {
      // Если нет координат, показываем карту по умолчанию
      console.log('Показываем карту по умолчанию (нет координат)')
      mapInstanceRef.current = new window.ymaps.Map(mapContainerRef.current, {
        center: [59.939095, 30.315868], // Санкт-Петербург
        zoom: 10,
      })
      return
    }

    // Вычисляем центр карты (среднее арифметическое всех координат)
    const avgLat = blocksWithCoords.reduce((sum, b) => sum + b.coord.lat, 0) / blocksWithCoords.length
    const avgLon = blocksWithCoords.reduce((sum, b) => sum + b.coord.lon, 0) / blocksWithCoords.length

    console.log('Центр карты:', avgLat, avgLon)

    // Создаем карту
    try {
      console.log('Создание карты с центром:', avgLat, avgLon)
      mapInstanceRef.current = new window.ymaps.Map(mapContainerRef.current, {
        center: [avgLat, avgLon],
        zoom: 11,
      })
      console.log('Карта создана успешно')
    } catch (error) {
      console.error('Ошибка при создании карты:', error)
      return
    }

    // Создаем кластеризатор для группировки маркеров
    const clusterer = new window.ymaps.Clusterer({
      clusterDisableClickZoom: true,
      clusterOpenBalloonOnClick: false, // Отключаем автоматическое открытие балуна
      clusterBalloonContentLayout: 'cluster#balloonCarousel',
      clusterBalloonItemContentLayout: 'cluster#balloonCarouselItem',
      clusterBalloonPanelMaxMapArea: 0,
      clusterBalloonContentLayoutWidth: 200,
      clusterBalloonContentLayoutHeight: 130,
      clusterBalloonPagerSize: 5,
      clusterBalloonPagerType: 'marker',
      clusterHideIconOnBalloonOpen: false,
      geoObjectHideIconOnBalloonOpen: false,
      zoomOnClick: true, // Разрешаем зум при клике на кластер
      // Настройки кластеризации
      gridSize: 64, // Размер сетки для кластеризации
      groupByCoordinates: false, // Не группировать по координатам
    })
    
    clustererRef.current = clusterer

    // Создаем коллекцию маркеров
    const markersCollection = []

    // Добавляем метки для всех блоков с кастомным HTML
    blocksWithCoords.forEach((block) => {
      const { coord, blockId, blockGuid, blockName, priceText, apartmentsCount } = block
      
      // Создаем кастомный HTML для маркера с ценой
      const markerHtml = `
        <div class="marker-with-text marker-with-text_building" style="
          display: flex;
          flex-direction: column;
          align-items: center;
          cursor: pointer;
          pointer-events: auto;
        ">
          <div class="marker-with-text__content-wrapper" style="
            background: white;
            border-radius: 20px;
            padding: 4px 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.15);
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 13px;
            font-weight: 500;
            color: #1a1a1a;
            white-space: nowrap;
            pointer-events: auto;
          ">
            <svg width="15" height="14" viewBox="0 0 15 14" fill="none" xmlns="http://www.w3.org/2000/svg" style="flex-shrink: 0; pointer-events: none;">
              <path fill-rule="evenodd" clip-rule="evenodd" d="M1.079 4.109c0-.569.347-1.08.876-1.288L7.463.79c.909-.36 1.716.413 1.716 1.39V12.4h.9V5.402c0-.54.66-.873 1.122-.59l1.815.982c.412.251.663.699.663 1.181V12.4h.431c.259 0 .469.173.469.431 0 .26-.21.47-.469.47H.647a.469.469 0 0 1-.468-.47c0-.258.21-.43.468-.43h.432V4.108Z" fill="red"/>
            </svg>
            ${priceText || '—'}
          </div>
          <svg class="pin-smooth-arrow" width="16" height="8" viewBox="0 0 16 8" fill="none" xmlns="http://www.w3.org/2000/svg" style="margin-top: -1px; pointer-events: none;">
            <path d="M0 0h16a8.073 8.073 0 0 0-7.884 6.945l-.038.267c-.018.128-.203.128-.221 0l-.046-.324A7.998 7.998 0 0 0 0 0Z" fill="#FAFAFA"/>
          </svg>
        </div>
      `

      // Создаем кастомную иконку через templateLayoutFactory с правильной обработкой событий
      const customIconLayout = window.ymaps.templateLayoutFactory.createClass(markerHtml, {
        build: function() {
          customIconLayout.superclass.build.call(this)
          // Убеждаемся, что маркер кликабелен
          const element = this.getParentElement()
          if (element) {
            element.style.pointerEvents = 'auto'
            element.style.cursor = 'pointer'
          }
        }
      })

      const marker = new window.ymaps.Placemark(
        [coord.lat, coord.lon],
        {
          balloonContent: `<div><strong>${blockName}</strong>${apartmentsCount > 0 ? `<br/>Квартир: ${apartmentsCount}` : ''}${priceText ? `<br/>Цена: ${priceText}` : ''}<br/><a href="/trendagent/apartments/${blockId}${blockGuid ? `?guid=${blockGuid}` : ''}">Перейти к объекту</a></div>`,
          hintContent: `${blockName}${priceText ? ` - ${priceText}` : ''}`,
        },
        {
          iconLayout: customIconLayout,
          iconImageSize: [90, 36],
          iconImageOffset: [-45, -36],
          iconShape: {
            type: 'Rectangle',
            coordinates: [[-45, -36], [45, 0]]
          },
          // Отключаем перетаскивание карты при клике на маркер
          draggable: false,
        }
      )

      // Обработчик клика на маркер - используем stopPropagation для предотвращения перетаскивания карты
      marker.events.add('click', (e) => {
        e.stopPropagation()
        e.preventDefault()
        setSelectedBlock(block)
        loadBlockData(blockId)
      })
      
      // Обработчик для предотвращения перетаскивания карты при клике на маркер
      marker.events.add('mousedown', (e) => {
        e.stopPropagation()
      })

      // Обработчик наведения для изменения курсора
      marker.events.add('mouseenter', () => {
        if (mapInstanceRef.current) {
          mapInstanceRef.current.container.getElement().style.cursor = 'pointer'
        }
      })

      marker.events.add('mouseleave', () => {
        if (mapInstanceRef.current) {
          mapInstanceRef.current.container.getElement().style.cursor = ''
        }
      })

      markersCollection.push(marker)
      markersRef.current.push(marker)
    })

    // Добавляем все маркеры в кластеризатор
    clusterer.add(markersCollection)
    
    // Добавляем кластеризатор на карту
    mapInstanceRef.current.geoObjects.add(clusterer)

    // Обработчик клика на кластер
    clusterer.events.add('click', (e) => {
      const target = e.get('target')
      if (target instanceof window.ymaps.ClusterPlacemark) {
        // При клике на кластер увеличиваем зум
        const clusterCenter = target.geometry.getCoordinates()
        mapInstanceRef.current.setCenter(clusterCenter, mapInstanceRef.current.getZoom() + 2, {
          duration: 300
        })
      }
    })

    console.log('Добавлено меток:', markersRef.current.length)

    // Автоматически подгоняем границы карты под все метки
    if (blocksWithCoords.length > 1) {
      mapInstanceRef.current.setBounds(
        clusterer.getBounds(),
        {
          checkZoomRange: true,
          duration: 300,
        }
      )
    }
  }

  if (!authData || !authData.authenticated) {
    return (
      <div className="objects-map-container">
        <div className="loading">
          <div className="spinner"></div>
          <p>Авторизация...</p>
        </div>
      </div>
    )
  }

  return (
    <div className="objects-map-container">
      <div className="objects-map-header">
        <button className="btn-back" onClick={() => navigate('/')}>
          <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
            <path d="M12.5 15L7.5 10L12.5 5" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
          </svg>
          Назад
        </button>
        <h1>Объекты на карте</h1>
      </div>

      {loading ? (
        <div className="loading">
          <div className="spinner"></div>
          <p>Загрузка объектов...</p>
        </div>
      ) : (
        <>
          <div className="map-info">
            Найдено объектов: {objects.length}
          </div>

          <div className="map-wrapper">
            <div
              ref={mapContainerRef}
              className="map-container"
              style={{ width: '100%', height: '600px' }}
            />
          </div>

          {/* Карточка блока при клике на маркер */}
          {selectedBlock && (
            <div className="block-card-overlay" onClick={() => setSelectedBlock(null)}>
              <div className="block-card" onClick={(e) => e.stopPropagation()}>
                {loadingBlock ? (
                  <div className="loading">
                    <div className="spinner"></div>
                    <p>Загрузка данных...</p>
                  </div>
                ) : (
                  <>
                    <div className="block-card__header">
                      <h2>{selectedBlock.blockName}</h2>
                      <button 
                        className="block-card__close"
                        onClick={() => setSelectedBlock(null)}
                        aria-label="Закрыть"
                      >
                        <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
                          <path fillRule="evenodd" clipRule="evenodd" d="M13.8536 6.14645C14.0488 6.34171 14.0488 6.65829 13.8536 6.85355L10.707 10.0001L13.8536 13.1467C14.0488 13.342 14.0488 13.6585 13.8536 13.8538C13.6583 14.0491 13.3417 14.0491 13.1464 13.8538L9.99988 10.7072L6.85355 13.8536C6.65829 14.0488 6.34171 14.0488 6.14645 13.8536C5.95118 13.6583 5.95118 13.3417 6.14645 13.1464L9.29277 10.0001L6.14645 6.8538C5.95118 6.65854 5.95118 6.34195 6.14645 6.14669C6.34171 5.95143 6.65829 5.95143 6.85355 6.14669L9.99988 9.29302L13.1465 6.14645C13.3417 5.95118 13.6583 5.95118 13.8536 6.14645Z" fill="#4C4C4C"/>
                        </svg>
                      </button>
                    </div>
                    
                    {blockData && (
                      <div className="block-card__content">
                        {blockGallery && blockGallery.length > 0 && (
                          <div className="block-card__images">
                            <img 
                              src={blockGallery[0]?.url || blockGallery[0]?.path || ''} 
                              alt={selectedBlock.blockName}
                              className="block-card__image"
                            />
                          </div>
                        )}
                        
                        <div className="block-card__info">
                          {blockData.address && (
                            <div className="block-card__address">
                              <strong>Адрес:</strong> {Array.isArray(blockData.address) ? blockData.address.join(', ') : blockData.address}
                            </div>
                          )}
                          
                          {blockData.builder && (
                            <div className="block-card__builder">
                              <strong>Застройщик:</strong> {blockData.builder.name || blockData.builder}
                            </div>
                          )}
                          
                          {blockData.deadline && (
                            <div className="block-card__deadline">
                              <strong>Срок сдачи:</strong> {blockData.deadline}
                            </div>
                          )}
                          
                          {blockData.apartmentsMinPrices && blockData.apartmentsMinPrices.length > 0 && (
                            <div className="block-card__prices">
                              <strong>Цены:</strong>
                              <ul>
                                {blockData.apartmentsMinPrices.map((price, idx) => (
                                  <li key={idx}>
                                    {price.rooms || price.room || '—'}: от {price.price || price.price_value || '—'} ₽
                                  </li>
                                ))}
                              </ul>
                            </div>
                          )}
                          
                          {blockData.apart_count && (
                            <div className="block-card__count">
                              <strong>Квартир:</strong> {blockData.apart_count}
                            </div>
                          )}
                        </div>
                        
                        <div className="block-card__footer">
                          <a 
                            href={`/trendagent/apartments/${selectedBlock.blockId}${selectedBlock.blockGuid ? `?guid=${selectedBlock.blockGuid}` : ''}`}
                            className="block-card__link"
                          >
                            Перейти к объекту
                          </a>
                        </div>
                      </div>
                    )}
                  </>
                )}
              </div>
            </div>
          )}

          <div className="objects-list">
            <h2>Список объектов</h2>
            <div className="objects-grid">
              {objects.slice(0, 20).map((obj, idx) => {
                const coord = getCoordinates(obj)
                const blockId = obj.block_id || obj._id || obj.id
                const blockGuid = obj.guid
                const name = obj.block_name || obj.name || obj.title || 'Без названия'
                
                return (
                  <div key={obj.id || obj._id || idx} className="object-map-card">
                    <h3 className="object-map-name">{name}</h3>
                    {coord && (
                      <div className="object-map-coords">
                        Координаты: {coord.lat.toFixed(6)}, {coord.lon.toFixed(6)}
                      </div>
                    )}
                    <a
                      href={`/trendagent/apartments/${blockId}${blockGuid ? `?guid=${blockGuid}` : ''}`}
                      className="object-map-link"
                    >
                      Перейти к объекту
                    </a>
                  </div>
                )
              })}
            </div>
          </div>
        </>
      )}
    </div>
  )
}

export default ObjectsMap
