import { useState, useEffect } from 'react'
import { useNavigate } from 'react-router-dom'
import { trendAgentAPI } from '../services/api'
import './VillagesMap.css'

const VillagesMap = () => {
  const navigate = useNavigate()
  const [authData, setAuthData] = useState(null)
  const [villages, setVillages] = useState([])
  const [loading, setLoading] = useState(false)
  const [filters, setFilters] = useState({
    city: '58c665588b6aa52311afa01b',
    phone: '+79045393434',
    password: 'nwBvh4q',
  })

  useEffect(() => {
    authenticate()
  }, [])

  useEffect(() => {
    if (authData && authData.authenticated) {
      loadVillages()
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

  const loadVillages = async () => {
    if (!authData || !authData.authenticated) return

    setLoading(true)
    try {
      const params = {
        phone: filters.phone,
        password: filters.password,
        object_type: 'villages',
        count: 1000,
        offset: 0,
        ...filters,
      }

      const response = await trendAgentAPI.getObjectsList('villages', params)

      if (response.success) {
        const villagesList = response.data?.objects || response.data?.data || []
        setVillages(villagesList)
      }
    } catch (error) {
      console.error('Ошибка загрузки поселков:', error)
      setVillages([])
    } finally {
      setLoading(false)
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

  const getMapUrl = () => {
    const coords = villages
      .map(obj => {
        const coord = getCoordinates(obj)
        return coord ? `${coord.lat},${coord.lon}` : null
      })
      .filter(Boolean)
      .slice(0, 100)

    if (coords.length === 0) {
      return 'https://yandex.ru/maps/?pt=30.315868,59.939095&z=10'
    }

    const firstCoord = getCoordinates(villages[0])
    if (firstCoord) {
      return `https://yandex.ru/maps/?pt=${firstCoord.lon},${firstCoord.lat}&z=11`
    }

    return 'https://yandex.ru/maps/?pt=30.315868,59.939095&z=10'
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
        <h1>Посёлки на карте</h1>
      </div>

      <div className="villages-tabs">
        <button className="tab" onClick={() => navigate('/villages/list')}>
          Посёлки
        </button>
        <button className="tab" onClick={() => navigate('/villages/plots')}>
          Участки
        </button>
        <button className="tab active">На карте</button>
      </div>

      {loading ? (
        <div className="loading">
          <div className="spinner"></div>
          <p>Загрузка поселков...</p>
        </div>
      ) : (
        <>
          <div className="map-info">
            Найдено поселков: {villages.length}
          </div>

          <div className="map-wrapper">
            <iframe
              src={getMapUrl()}
              width="100%"
              height="600"
              frameBorder="0"
              allowFullScreen
              className="map-iframe"
            />
          </div>

          <div className="objects-list">
            <h2>Список поселков</h2>
            <div className="objects-grid">
              {villages.slice(0, 20).map((village, idx) => {
                const coord = getCoordinates(village)
                const villageId = village._id || village.id
                const villageGuid = village.guid
                const name = village.name || village.village_name || village.title || 'Без названия'
                
                return (
                  <div key={village.id || village._id || idx} className="object-map-card">
                    <h3 className="object-map-name">{name}</h3>
                    {coord && (
                      <div className="object-map-coords">
                        Координаты: {coord.lat.toFixed(6)}, {coord.lon.toFixed(6)}
                      </div>
                    )}
                    <a
                      href={`/plots/${villageId}${villageGuid ? `?guid=${villageGuid}` : ''}`}
                      className="object-map-link"
                    >
                      Перейти к поселку
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

export default VillagesMap
