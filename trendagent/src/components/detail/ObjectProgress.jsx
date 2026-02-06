import { useState } from 'react'
import './ObjectProgress.css'
import { getImageUrl } from '../../utils/imageUtils'

const ObjectProgress = ({ progressData }) => {
  const [selectedYear, setSelectedYear] = useState(null)

  const progress = Array.isArray(progressData?.data) 
    ? progressData.data 
    : (Array.isArray(progressData) ? progressData : [])
  const years = [...new Set(progress.map(p => {
    if (p.date) {
      return new Date(p.date).getFullYear()
    }
    return null
  }).filter(Boolean))].sort((a, b) => b - a)

  const filteredProgress = selectedYear
    ? progress.filter(p => {
        if (p.date) {
          return new Date(p.date).getFullYear() === parseInt(selectedYear)
        }
        return false
      })
    : progress

  return (
    <div className="object-progress card">
      <h2 className="section-title">Ход строительства</h2>
      
      {years.length > 0 && (
        <div className="progress-years">
          <button
            className={`year-btn ${!selectedYear ? 'active' : ''}`}
            onClick={() => setSelectedYear(null)}
          >
            Все
          </button>
          {years.map(year => (
            <button
              key={year}
              className={`year-btn ${selectedYear === year ? 'active' : ''}`}
              onClick={() => setSelectedYear(year)}
            >
              {year}
            </button>
          ))}
        </div>
      )}

      <div className="progress-grid">
        {filteredProgress.map((item, index) => (
          <div key={item._id || index} className="progress-item">
            {item.image && (
              <img
                src={getImageUrl(item.image)}
                alt={item.title || item.name || `Прогресс ${index + 1}`}
                className="progress-image"
                onError={(e) => { e.target.style.display = 'none' }}
              />
            )}
            <div className="progress-content">
              {(item.title || item.name) && (
                <h3 className="progress-title">{item.title || item.name}</h3>
              )}
              {item.date && (
                <span className="progress-date">
                  {new Date(item.date).toLocaleDateString('ru-RU')}
                </span>
              )}
              {item.description && (
                <p className="progress-description">{item.description}</p>
              )}
            </div>
          </div>
        ))}
      </div>

      {filteredProgress.length === 0 && (
        <div className="empty-state">
          <p>Информация о ходе строительства отсутствует</p>
        </div>
      )}
    </div>
  )
}

export default ObjectProgress
