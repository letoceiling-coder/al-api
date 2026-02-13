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

  const YANDEX_MAPS_API_KEY = 'a79c56f4-efea-471e-bee5-fe9226cd53fd'
  const mapContainerRef = useRef(null)
  const mapInstanceRef = useRef(null)
  const markersRef = useRef([])
  const clustererRef = useRef(null)

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
        count: 1000,
        offset: 0,
        room: filters.room,
      }

      try {
        const blocksResponse = await trendAgentAPI.getApartments({
          ...params,
          show_type: 'map',
        })
        
        if (blocksResponse.success) {
          const blocksList = blocksResponse.data?.objects || blocksResponse.data?.data || []
          setObjects(blocksList)
          return
        }
      } catch (error) {
        console.error('Ошибка загрузки блоков для карты:', error)
      }

      const response = await trendAgentAPI.getApartments(params)
      if (response.success) {
        const objectsList = response.data?.objects || response.data?.data || []
        setObjects(objectsList)
      }
    } catch (error) {
      console.error('Ошибка загрузки объектов:', error)
      setObjects([])
    } finally {
      setLoading(false)
    }
  }

  const getMinPrice = (block) => {
    if (block.apartmentsMinPrices && Array.isArray(block.apartmentsMinPrices) && block.apartmentsMinPrices.length > 0) {
      const prices = block.apartmentsMinPrices
        .map(p => {
          const priceStr = p.price || p.price_value || ''
          const priceNum = parseFloat(priceStr.replace(/\s/g, ''))
          return isNaN(priceNum) ? null : priceNum
        })
        .filter(p => p !== null)
      
      if (prices.length > 0) {
        return Math.min(...prices)
      }
    }
    
    if (block.min_price) {
      const priceNum = typeof block.min_price === 'number' ? block.min_price : parseFloat(block.min_price)
      if (!isNaN(priceNum)) {
        return priceNum
      }
    }
    
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

  const formatPriceForMarker = (price) => {
    if (!price) return null
    const priceInMillions = price / 1000000
    if (priceInMillions >= 1) {
      return `${priceInMillions.toFixed(1)} млн`
    }
    return `${Math.round(price / 1000)} тыс`
  }

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

  const getCoordinates = (obj) => {
    if (obj.coordinates) {
      if (Array.isArray(obj.coordinates) && obj.coordinates.length >= 2) {
        return { lat: obj.coordinates[0], lon: obj.coordinates[1] }
      }
      if (obj.coordinates.lat && obj.coordinates.lon) {
        return { lat: obj.coordinates.lat, lon: obj.coordinates.lon }
      }
      if (obj.coordinates.latitude && obj.coordinates.longitude) {
        return { lat: obj.coordinates.latitude, lon: obj.coordinates.longitude }
      }
    }
    
    if (obj.geo && obj.geo.coordinates) {
      if (Array.isArray(obj.geo.coordinates) && obj.geo.coordinates.length >= 2) {
        return { lat: obj.geo.coordinates[1], lon: obj.geo.coordinates[0] }
      }
      if (obj.geo.coordinates.lat && obj.geo.coordinates.lon) {
        return { lat: obj.geo.coordinates.lat, lon: obj.geo.coordinates.lon }
      }
    }
    
    if (obj.location) {
      if (obj.location.coordinates && Array.isArray(obj.location.coordinates) && obj.location.coordinates.length >= 2) {
        return { lat: obj.location.coordinates[1], lon: obj.location.coordinates[0] }
      }
      if (obj.location.lat && obj.location.lon) {
        return { lat: obj.location.lat, lon: obj.location.lon }
      }
      if (obj.location.latitude && obj.location.longitude) {
        return { lat: obj.location.latitude, lon: obj.location.longitude }
      }
    }
    
    if (obj.latitude && obj.longitude) {
      return { lat: parseFloat(obj.latitude), lon: parseFloat(obj.longitude) }
    }
    if (obj.lat && obj.lon) {
      return { lat: parseFloat(obj.lat), lon: parseFloat(obj.lon) }
    }
    
    return null
  }

  // Инициализация Яндекс.Карт
  useEffect(() => {
    if (loading || !mapContainerRef.current || objects.length === 0) {
      return
    }

    if (!window.ymaps) {
      const script = document.createElement('script')
      script.src = `https://api-maps.yandex.ru/2.1/?apikey=${YANDEX_MAPS_API_KEY}&lang=ru_RU`
      script.async = true
      script.onload = () => {
        if (window.ymaps) {
          window.ymaps.ready(() => {
            if (mapContainerRef.current && objects.length > 0) {
              initMap()
            }
          })
        }
      }
      document.head.appendChild(script)
    } else {
      window.ymaps.ready(() => {
        if (mapContainerRef.current && objects.length > 0) {
          initMap()
        }
      })
    }

    return () => {
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
    if (!mapContainerRef.current || !window.ymaps) {
      return
    }

    // Очистка предыдущей карты
    if (clustererRef.current) {
      try {
        if (mapInstanceRef.current && mapInstanceRef.current.geoObjects) {
          mapInstanceRef.current.geoObjects.remove(clustererRef.current)
        }
        clustererRef.current.removeAll()
      } catch (e) {
        console.warn('Ошибка при очистке кластеризатора:', e)
      }
      clustererRef.current = null
    }
    if (mapInstanceRef.current) {
      try {
        mapInstanceRef.current.destroy()
      } catch (e) {
        console.warn('Ошибка при уничтожении карты:', e)
      }
      mapInstanceRef.current = null
      markersRef.current = []
    }

    // Обработка блоков
    let blocksWithCoords = []
    
    if (objects.length > 0 && (objects[0]._id || objects[0].id) && !objects[0].block_id) {
      blocksWithCoords = objects
      .map(obj => {
          try {
            const coord = getCoordinates(obj)
            if (!coord) return null
            
            const minPrice = getMinPrice(obj)
            const priceText = formatPriceForMarker(minPrice)
            
            return {
              blockId: obj._id || obj.id,
              blockGuid: obj.guid,
              blockName: obj.name || obj.title || 'Без названия',
              coord,
              apartmentsCount: obj.places_count || obj.apartments_count || obj.apart_count || 0,
              minPrice,
              priceText,
              blockData: obj
            }
          } catch (e) {
            console.warn('Ошибка при обработке блока:', e, obj)
            return null
          }
        })
        .filter(Boolean)
    } else {
      const blocksMap = new Map()
      
      objects.forEach(obj => {
        try {
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
          
        const coord = getCoordinates(obj)
          if (coord && !blocksMap.get(blockId).coord) {
            blocksMap.get(blockId).coord = coord
          }
        } catch (e) {
          console.warn('Ошибка при обработке квартиры:', e, obj)
        }
      })

      blocksWithCoords = Array.from(blocksMap.values())
        .map(block => {
          if (!block.coord) {
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

    if (blocksWithCoords.length === 0) {
      mapInstanceRef.current = new window.ymaps.Map(mapContainerRef.current, {
        center: [59.939095, 30.315868],
        zoom: 10,
      })
      return
    }

    // Вычисляем центр карты
    const avgLat = blocksWithCoords.reduce((sum, b) => sum + b.coord.lat, 0) / blocksWithCoords.length
    const avgLon = blocksWithCoords.reduce((sum, b) => sum + b.coord.lon, 0) / blocksWithCoords.length

    // Создаем карту
    try {
      mapInstanceRef.current = new window.ymaps.Map(mapContainerRef.current, {
        center: [avgLat, avgLon],
        zoom: 11,
      })
    } catch (error) {
      console.error('Ошибка при создании карты:', error)
      return
    }

    // Создаем кластеризатор
    let clusterer
    try {
      clusterer = new window.ymaps.Clusterer({
        clusterDisableClickZoom: true,
        clusterOpenBalloonOnClick: false,
        zoomOnClick: true,
        gridSize: 64,
        groupByCoordinates: false,
      })
      clustererRef.current = clusterer
    } catch (error) {
      console.error('Ошибка при создании кластеризатора:', error)
      clusterer = null
      clustererRef.current = null
    }

    // Создаем маркеры
    const markersCollection = []

    blocksWithCoords.forEach((block) => {
      const { coord, blockId, blockGuid, blockName, priceText, apartmentsCount } = block
      
      // HTML для маркера
      const markerHtml = `
        <div class="marker-with-text" style="
          width: 90px;
          height: 36px;
          display: flex;
          flex-direction: column;
          align-items: center;
          justify-content: flex-start;
          cursor: pointer;
          pointer-events: auto;
          position: relative;
          margin: 0;
          padding: 0;
          box-sizing: border-box;
        ">
          <div style="
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
          <svg width="16" height="8" viewBox="0 0 16 8" fill="none" xmlns="http://www.w3.org/2000/svg" style="margin-top: -1px; pointer-events: none; display: block; flex-shrink: 0;">
            <path d="M0 0h16a8.073 8.073 0 0 0-7.884 6.945l-.038.267c-.018.128-.203.128-.221 0l-.046-.324A7.998 7.998 0 0 0 0 0Z" fill="#FAFAFA"/>
          </svg>
        </div>
      `

      // Создаем layout через templateLayoutFactory
      const customIconLayout = window.ymaps.templateLayoutFactory.createClass(markerHtml)

      // Создаем маркер по документации Яндекс.Карт
      // iconImageOffset: смещение точки привязки от ЦЕНТРА иконки
      // Маркер 90x36px, точка привязки внизу по центру (где стрелка)
      // От центра [0,0] до точки привязки: [0, 18] (вниз на половину высоты)
      const marker = new window.ymaps.Placemark(
        [coord.lat, coord.lon],
        {
          balloonContent: `<div><strong>${blockName}</strong>${apartmentsCount > 0 ? `<br/>Квартир: ${apartmentsCount}` : ''}${priceText ? `<br/>Цена: ${priceText}` : ''}<br/><a href="/trendagent/apartments/${blockId}${blockGuid ? `?guid=${blockGuid}` : ''}">Перейти к объекту</a></div>`,
          hintContent: `${blockName}${priceText ? ` - ${priceText}` : ''}`,
        },
        {
          iconLayout: customIconLayout,
          iconImageSize: [90, 36],
          // По документации Яндекс.Карт для templateLayoutFactory:
          // iconImageOffset - смещение точки привязки от ЛЕВОГО ВЕРХНЕГО УГЛА HTML-элемента
          // Если область клика находится на высоте маркера выше визуального маркера,
          // значит точка привязки должна быть на уровне верха маркера по центру
          // От левого верхнего угла [0,0] до точки привязки: центр по X (45px), верх по Y (0px)
          iconImageOffset: [45, 0],
          // iconShape: область клика относительно точки привязки [0, 0]
          // Точка привязки [0,0] на уровне верха маркера по центру
          // Область клика: от [-45, 0] (левый верхний угол маркера) до [45, 36] (правый нижний угол)
          iconShape: {
            type: 'Rectangle',
            coordinates: [[-45, 0], [45, 36]]
          },
          draggable: false,
        }
      )

      // Обработчики событий
      marker.events.add('click', (e) => {
        e.stopPropagation()
        setSelectedBlock(block)
        loadBlockData(blockId)
      })

      markersCollection.push(marker)
      markersRef.current.push(marker)
    })

    // Добавляем маркеры на карту
    try {
      if (clusterer) {
        clusterer.add(markersCollection)
        mapInstanceRef.current.geoObjects.add(clusterer)
        
        clusterer.events.add('click', (e) => {
          const target = e.get('target')
          if (target instanceof window.ymaps.ClusterPlacemark) {
            const clusterCenter = target.geometry.getCoordinates()
            mapInstanceRef.current.setCenter(clusterCenter, mapInstanceRef.current.getZoom() + 2, {
              duration: 300
            })
          }
        })
      } else {
        markersCollection.forEach(marker => {
          mapInstanceRef.current.geoObjects.add(marker)
        })
      }
    } catch (error) {
      console.error('Ошибка при добавлении маркеров:', error)
    }

    // Устанавливаем границы карты
    if (blocksWithCoords.length > 1) {
      try {
        let bounds
        if (clusterer) {
          bounds = clusterer.getBounds()
        } else {
          bounds = mapInstanceRef.current.geoObjects.getBounds()
        }
        
        if (bounds) {
          mapInstanceRef.current.setBounds(bounds, {
            checkZoomRange: true,
            duration: 300,
          })
        }
      } catch (error) {
        console.warn('Ошибка при установке границ карты:', error)
      }
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
        <button type="button" className="btn btn-outline btn-back" onClick={() => navigate('/')}>
          <svg className="w-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden>
            <path strokeLinecap="round" strokeLinejoin="round" strokeWidth="2" d="M15 19l-7-7 7-7" />
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
                              src={blockGallery[0]?.url || blockGallery[0]?.path || blockGallery[0]?.image?.url || blockGallery[0]?.image?.path || blockGallery[0]?.src || ''} 
                              alt={selectedBlock.blockName}
                              className="block-card__image"
                              onError={(e) => {
                                const nextImage = blockGallery.find((img, idx) => idx > 0 && (img.url || img.path || img.image?.url || img.image?.path || img.src))
                                if (nextImage) {
                                  e.target.src = nextImage.url || nextImage.path || nextImage.image?.url || nextImage.image?.path || nextImage.src
                                } else {
                                  e.target.style.display = 'none'
                                }
                              }}
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
        </>
      )}
    </div>
  )
}

export default ObjectsMap
