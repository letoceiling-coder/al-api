import { useState, useEffect } from 'react'
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
    if (obj.coordinates) {
      if (Array.isArray(obj.coordinates) && obj.coordinates.length >= 2) {
        return { lat: obj.coordinates[0], lon: obj.coordinates[1] }
      }
      if (obj.coordinates.lat && obj.coordinates.lon) {
        return { lat: obj.coordinates.lat, lon: obj.coordinates.lon }
      }
    }
    if (obj.geo && obj.geo.coordinates) {
      if (Array.isArray(obj.geo.coordinates) && obj.geo.coordinates.length >= 2) {
        return { lat: obj.geo.coordinates[0], lon: obj.geo.coordinates[1] }
      }
    }
    if (obj.latitude && obj.longitude) {
      return { lat: obj.latitude, lon: obj.longitude }
    }
    return null
  }

  // API ключ Яндекс.Карт
  const YANDEX_MAPS_API_KEY = 'a79c56f4-efea-471e-bee5-fe9226cd53fd'

  // Формируем URL для виджета Яндекс.Карт с API ключом
  const getMapUrl = () => {
    if (objects.length === 0) {
      // Центр по умолчанию - Санкт-Петербург
      return `https://yandex.ru/map-widget/v1/?pt=30.315868,59.939095&z=10&l=map&apikey=${YANDEX_MAPS_API_KEY}`
    }

    // Используем первый объект как центр карты
    const firstCoord = getCoordinates(objects[0])
    if (firstCoord) {
      return `https://yandex.ru/map-widget/v1/?pt=${firstCoord.lon},${firstCoord.lat}&z=11&l=map&apikey=${YANDEX_MAPS_API_KEY}`
    }

    // Центр по умолчанию - Санкт-Петербург
    return `https://yandex.ru/map-widget/v1/?pt=30.315868,59.939095&z=10&l=map&apikey=${YANDEX_MAPS_API_KEY}`
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
            <iframe
              src={getMapUrl()}
              width="100%"
              height="600"
              frameBorder="0"
              allowFullScreen
              className="map-iframe"
              title="Карта объектов"
              loading="lazy"
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
