import { useState, useEffect } from 'react'
import { useParams, useNavigate } from 'react-router-dom'
import { trendAgentAPI } from '../services/api'
import { getImageUrl } from '../utils/imageUtils'
import './HouseProjectDetail.css'

const HouseProjectDetail = () => {
  const { slug } = useParams()
  const navigate = useNavigate()
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState(null)
  const [projectData, setProjectData] = useState(null)
  const [viewMode, setViewMode] = useState('all') // 'all', 'diff'
  const [phone, setPhone] = useState('+79045393434')
  const [password, setPassword] = useState('nwBvh4q')

  useEffect(() => {
    loadProjectDetail()
  }, [slug])

  const loadProjectDetail = async () => {
    setLoading(true)
    setError(null)

    try {
      const params = {
        phone,
        password,
        object_type: 'contractors',
      }

      // Получаем детальную информацию о проекте
      const response = await trendAgentAPI.getHouseProjectDetail(slug, params)

      if (response.success) {
        // Данные могут быть в разных местах в зависимости от структуры ответа
        const projectData = response.data?.unified?.data || 
                           response.data?.data?.unified?.data || 
                           response.data?.data || 
                           response.data
        
        if (projectData) {
          setProjectData(projectData)
        } else {
          setError('Проект не найден')
        }
      } else {
        setError(response.message || 'Ошибка загрузки проекта')
      }
    } catch (err) {
      console.error('Ошибка загрузки проекта:', err)
      setError(err.message || 'Ошибка загрузки проекта')
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
      <div className="house-project-detail-container">
        <div className="loading">
          <div className="spinner"></div>
          <p>Загрузка данных...</p>
        </div>
      </div>
    )
  }

  if (error) {
    return (
      <div className="house-project-detail-container">
        <div className="error-message">
          <p>{error}</p>
          <button className="btn btn-primary" onClick={() => navigate(-1)}>
            Назад
          </button>
        </div>
      </div>
    )
  }

  if (!projectData) {
    return (
      <div className="house-project-detail-container">
        <div className="error-message">
          <p>Данные проекта не найдены</p>
          <button className="btn btn-primary" onClick={() => navigate(-1)}>
            Назад
          </button>
        </div>
      </div>
    )
  }

  const project = projectData
  const imageUrl = project.images && project.images.length > 0 
    ? getImageUrl(project.images[0]) 
    : null
  const name = project.name?.label || project.name || project.title || 'Без названия'
  const area = project.area_total || project.total_area || project.area || null
  const price = project.price || (project.prices && project.prices.length > 0 ? project.prices[0] : null)

  return (
    <div className="house-project-detail-container">
      <div className="project-header">
        <button className="btn-back" onClick={() => navigate(-1)}>
          <svg width="20" height="20" viewBox="0 0 20 20" fill="none">
            <path d="M12.5 15L7.5 10L12.5 5" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round"/>
          </svg>
          Назад
        </button>
        <h1>{name}</h1>
      </div>

      {imageUrl && (
        <div className="project-image-section">
          <img src={imageUrl} alt={name} className="project-main-image" />
        </div>
      )}

      <div className="project-tabs">
        <button
          className={`project-tab ${viewMode === 'all' ? 'active' : ''}`}
          onClick={() => setViewMode('all')}
        >
          Все параметры
        </button>
        <button
          className={`project-tab ${viewMode === 'diff' ? 'active' : ''}`}
          onClick={() => setViewMode('diff')}
        >
          Только отличия
        </button>
      </div>

      <div className="project-content">
        <div className="project-params-section">
          <h2>Параметры проекта</h2>
          <div className="params-grid">
            {area && (
              <div className="param-item">
                <span className="param-label">Общая площадь:</span>
                <span className="param-value">{area} м²</span>
              </div>
            )}
            {project.area_living && (
              <div className="param-item">
                <span className="param-label">Жилая площадь:</span>
                <span className="param-value">{project.area_living} м²</span>
              </div>
            )}
            {project.area_kitchen && (
              <div className="param-item">
                <span className="param-label">Площадь кухни:</span>
                <span className="param-value">{project.area_kitchen} м²</span>
              </div>
            )}
            {project.area_terrace && (
              <div className="param-item">
                <span className="param-label">Площадь террасы:</span>
                <span className="param-value">{project.area_terrace} м²</span>
              </div>
            )}
            {price && (
              <div className="param-item">
                <span className="param-label">Цена:</span>
                <span className="param-value">{formatPrice(price)}</span>
              </div>
            )}
          </div>
        </div>

        {project.description && (
          <div className="project-description-section">
            <h2>Описание</h2>
            <div className="description-content" dangerouslySetInnerHTML={{ __html: project.description }} />
          </div>
        )}
      </div>
    </div>
  )
}

export default HouseProjectDetail
