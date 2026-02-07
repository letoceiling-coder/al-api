import { useState, useEffect } from 'react'
import { useParams, useNavigate, useSearchParams } from 'react-router-dom'
import { trendAgentAPI } from '../services/api'
import { getImageUrl } from '../utils/imageUtils'
import './PlotDetail.css'

const PlotDetail = () => {
  const { slug, plotId } = useParams()
  const navigate = useNavigate()
  const [searchParams] = useSearchParams()
  const villageId = searchParams.get('village_id') || slug

  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const [plotData, setPlotData] = useState(null)
  const [villageData, setVillageData] = useState(null)
  const [phone, setPhone] = useState('+79045393434')
  const [password, setPassword] = useState('nwBvh4q')

  useEffect(() => {
    loadPlotDetail()
  }, [plotId, villageId])

  const loadPlotDetail = async () => {
    setLoading(true)
    setError(null)

    try {
      const params = {
        phone,
        password,
      }

      // Получаем данные поселка для контекста
      if (villageId) {
        try {
          const villageResponse = await trendAgentAPI.getPlotDetail(villageId, null, params)
          if (villageResponse.success) {
            setVillageData(villageResponse.data)
          }
        } catch (err) {
          console.warn('Не удалось загрузить данные поселка:', err)
        }
      }

      // Получаем детальную информацию об участке
      if (plotId && villageId) {
        try {
          const plotResponse = await trendAgentAPI.getPlotDetail(villageId, plotId, params)
          if (plotResponse.success) {
            setPlotData(plotResponse.data)
          } else {
            setError(plotResponse.message || 'Участок не найден')
          }
        } catch (err) {
          console.error('Ошибка загрузки данных участка:', err)
          setError(err.message || 'Ошибка загрузки данных участка')
        }
      } else {
        setError('Не указан ID участка или поселка')
      }
    } catch (err) {
      console.error('Ошибка загрузки данных участка:', err)
      setError(err.message || 'Ошибка загрузки данных участка')
    } finally {
      setLoading(false)
    }
  }

  const formatPrice = (price) => {
    if (!price || price === 0) return 'По запросу'
    return new Intl.NumberFormat('ru-RU', {
      style: 'currency',
      currency: 'RUB',
      minimumFractionDigits: 0,
    }).format(price)
  }

  if (loading) {
    return (
      <div className="plot-detail-container">
        <div className="loading">
          <div className="spinner"></div>
          <p>Загрузка данных...</p>
        </div>
      </div>
    )
  }

  if (error) {
    return (
      <div className="plot-detail-container">
        <div className="error-message">
          <p>{error}</p>
          <button className="btn btn-primary" onClick={() => navigate(-1)}>
            Назад
          </button>
        </div>
      </div>
    )
  }

  return (
    <div className="plot-detail-container">
      {/* Хлебные крошки */}
      <div className="breadcrumbs">
        <button className="btn-back" onClick={() => navigate(-1)}>
          <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
            <path d="M12.5 15L7.5 10L12.5 5" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
          </svg>
          Назад
        </button>
        {villageData && (
          <>
            <span className="breadcrumb-separator">/</span>
            <span className="breadcrumb-item">
              {villageData.name || villageData.village_name || 'Посёлок'}
            </span>
          </>
        )}
        <span className="breadcrumb-separator">/</span>
        <span className="breadcrumb-item active">Участок {plotId}</span>
      </div>

      <div className="plot-header">
        <h1>Участок {plotId}</h1>
        {villageData && (
          <p className="village-name">{villageData.name || villageData.village_name}</p>
        )}
      </div>

      <div className="plot-content">
        <div className="plot-main">
          <div className="plot-info-section">
            <h2>Информация об участке</h2>
            <div className="plot-info-grid">
              <div className="info-item">
                <span className="info-label">ID участка:</span>
                <span className="info-value">{plotId}</span>
              </div>
              {villageData && (
                <>
                  {villageData.min_prices && villageData.min_prices.length > 0 && (
                    <div className="info-item">
                      <span className="info-label">Цена от:</span>
                      <span className="info-value">
                        {formatPrice(villageData.min_prices[0].value || villageData.min_prices[0].price)}
                      </span>
                    </div>
                  )}
                  {villageData.area && (
                    <div className="info-item">
                      <span className="info-label">Площадь:</span>
                      <span className="info-value">{villageData.area} м²</span>
                    </div>
                  )}
                </>
              )}
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}

export default PlotDetail
