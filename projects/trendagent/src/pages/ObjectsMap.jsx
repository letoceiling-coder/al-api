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
    
    // Прямые поля
    if (obj.latitude && obj.longitude) {
      return { lat: obj.latitude, lon: obj.longitude }
    }
    if (obj.lat && obj.lon) {
      return { lat: obj.lat, lon: obj.lon }
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
    if (!mapContainerRef.current || objects.length === 0) return

    // Загружаем скрипт Яндекс.Карт, если еще не загружен
    if (!window.ymaps) {
      const script = document.createElement('script')
      script.src = `https://api-maps.yandex.ru/2.1/?apikey=${YANDEX_MAPS_API_KEY}&lang=ru_RU`
      script.async = true
      script.onload = () => {
        window.ymaps.ready(() => initMap())
      }
      document.head.appendChild(script)
    } else {
      window.ymaps.ready(() => initMap())
    }

    return () => {
      // Очищаем карту при размонтировании
      if (mapInstanceRef.current) {
        mapInstanceRef.current.destroy()
        mapInstanceRef.current = null
      }
      markersRef.current = []
    }
  }, [objects])

  const initMap = () => {
    if (!mapContainerRef.current || !window.ymaps) return

    // Уничтожаем предыдущую карту, если есть
    if (mapInstanceRef.current) {
      mapInstanceRef.current.destroy()
      markersRef.current = []
    }

    // Получаем координаты всех объектов
    const coords = objects
      .map(obj => {
        const coord = getCoordinates(obj)
        return coord ? { ...coord, obj } : null
      })
      .filter(Boolean)

    if (coords.length === 0) {
      // Если нет координат, показываем карту по умолчанию
      mapInstanceRef.current = new window.ymaps.Map(mapContainerRef.current, {
        center: [59.939095, 30.315868], // Санкт-Петербург
        zoom: 10,
      })
      return
    }

    // Вычисляем центр карты (среднее арифметическое всех координат)
    const avgLat = coords.reduce((sum, c) => sum + c.lat, 0) / coords.length
    const avgLon = coords.reduce((sum, c) => sum + c.lon, 0) / coords.length

    // Создаем карту
    mapInstanceRef.current = new window.ymaps.Map(mapContainerRef.current, {
      center: [avgLat, avgLon],
      zoom: 11,
    })

    // Добавляем метки для всех объектов
    coords.forEach(({ lat, lon, obj }) => {
      const blockId = obj.block_id || obj._id || obj.id
      const blockGuid = obj.guid
      const name = obj.block_name || obj.name || obj.title || 'Без названия'

      const marker = new window.ymaps.Placemark(
        [lat, lon],
        {
          balloonContent: `<div><strong>${name}</strong><br/><a href="/trendagent/apartments/${blockId}${blockGuid ? `?guid=${blockGuid}` : ''}">Перейти к объекту</a></div>`,
          hintContent: name,
        },
        {
          preset: 'islands#blueCircleDotIcon',
        }
      )

      mapInstanceRef.current.geoObjects.add(marker)
      markersRef.current.push(marker)
    })

    // Автоматически подгоняем границы карты под все метки
    if (coords.length > 1) {
      mapInstanceRef.current.setBounds(
        mapInstanceRef.current.geoObjects.getBounds(),
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
