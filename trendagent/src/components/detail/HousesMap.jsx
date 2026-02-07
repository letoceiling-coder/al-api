import './HousesMap.css'
import { getImageUrl } from '../../utils/imageUtils'

const HousesMap = ({ unifiedData, housesData }) => {
  if (!unifiedData && !housesData) {
    return null
  }

  // Получаем координаты из unified данных
  // Проверяем разные возможные структуры
  let coordinates = null
  let lat = null
  let lng = null
  
  if (unifiedData) {
    // Прямые координаты
    if (unifiedData.coordinates) {
      coordinates = unifiedData.coordinates
    } else if (unifiedData.location?.coordinates) {
      coordinates = unifiedData.location.coordinates
    } else if (unifiedData.point?.coordinates) {
      coordinates = unifiedData.point.coordinates
    } else if (unifiedData.location?.lat && unifiedData.location?.lng) {
      lat = unifiedData.location.lat
      lng = unifiedData.location.lng
    } else if (unifiedData.latitude && unifiedData.longitude) {
      lat = unifiedData.latitude
      lng = unifiedData.longitude
    } else if (unifiedData.lat && unifiedData.lon) {
      lat = unifiedData.lat
      lng = unifiedData.lon
    }
  }
  
  // Если coordinates - массив [lng, lat]
  if (Array.isArray(coordinates) && coordinates.length >= 2) {
    lng = coordinates[0]
    lat = coordinates[1]
  } else if (coordinates && typeof coordinates === 'object') {
    lat = coordinates.lat || coordinates.latitude || coordinates[1]
    lng = coordinates.lng || coordinates.lon || coordinates.longitude || coordinates[0]
  }

  const address = unifiedData?.address || ''

  // Если нет координат, показываем заглушку
  if (!lat || !lng) {
    return (
      <div className="houses-map-section">
        <h2>Карта</h2>
        <div className="houses-map-content">
          <div className="map-placeholder">
            <p>Карта будет отображаться здесь</p>
            {address && (
              <p className="map-address">Адрес: {address}</p>
            )}
          </div>
        </div>
      </div>
    )
  }


  // Используем Яндекс.Карты
  const yandexMapUrl = `https://yandex.ru/maps/?pt=${lng},${lat}&z=15&l=map`

  return (
    <div className="houses-map-section">
      <h2>Карта</h2>
      <div className="houses-map-content">
        <div className="map-container">
          <iframe
            src={`https://yandex.ru/map-widget/v1/?pt=${lng},${lat}&z=15&l=map`}
            width="100%"
            height="500"
            frameBorder="0"
            allowFullScreen
            title="Карта расположения"
            className="map-iframe"
          />
          <div className="map-info">
            {address && (
              <div className="map-address-info">
                <strong>Адрес:</strong> {address}
              </div>
            )}
            <div className="map-coordinates">
              <strong>Координаты:</strong> {lat}, {lng}
            </div>
            <a 
              href={yandexMapUrl} 
              target="_blank" 
              rel="noopener noreferrer"
              className="map-link"
            >
              Открыть в Яндекс.Картах
            </a>
          </div>
        </div>
      </div>
    </div>
  )
}

export default HousesMap
