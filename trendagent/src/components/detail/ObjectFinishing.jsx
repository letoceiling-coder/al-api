import './ObjectFinishing.css'
import { getImageUrl } from '../../utils/imageUtils'

const ObjectFinishing = ({ finishings }) => {
  const finishingsList = finishings || []

  if (!finishingsList || finishingsList.length === 0) {
    return (
      <div className="object-finishing card">
        <h2 className="section-title">Отделка</h2>
        <div className="empty-state">
          <p>Информация об отделке отсутствует</p>
        </div>
      </div>
    )
  }

  return (
    <div className="object-finishing card">
      <h2 className="section-title">Варианты отделки</h2>
      <div className="finishings-grid">
        {finishingsList.map((finishing, index) => (
          <div key={finishing._id || index} className="finishing-item">
            {finishing.image && (
              <img
                src={getImageUrl(finishing.image)}
                alt={finishing.name || finishing.title || `Отделка ${index + 1}`}
                className="finishing-image"
                onError={(e) => { e.target.style.display = 'none' }}
              />
            )}
            <div className="finishing-content">
              <h3 className="finishing-title">{finishing.name || finishing.title || `Отделка ${index + 1}`}</h3>
              {finishing.description && (
                <p className="finishing-description">{finishing.description}</p>
              )}
            </div>
          </div>
        ))}
      </div>
    </div>
  )
}

export default ObjectFinishing
