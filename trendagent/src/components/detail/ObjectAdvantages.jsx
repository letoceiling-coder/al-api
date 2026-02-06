import './ObjectAdvantages.css'
import { getImageUrl } from '../../utils/imageUtils'

const ObjectAdvantages = ({ advantages }) => {
  const advantagesList = advantages || []

  if (!advantagesList || advantagesList.length === 0) {
    return null
  }

  return (
    <div className="object-advantages card">
      <h2 className="section-title">Преимущества</h2>
      <div className="advantages-grid">
        {advantagesList.map((advantage, index) => (
          <div key={advantage._id || index} className="advantage-item">
            {advantage.image && (
              <img
                src={getImageUrl(advantage.image)}
                alt={advantage.name || advantage.title}
                className="advantage-image"
                onError={(e) => { e.target.style.display = 'none' }}
              />
            )}
            <div className="advantage-content">
              <h3 className="advantage-title">{advantage.name || advantage.title}</h3>
              {advantage.description && (
                <p className="advantage-description">{advantage.description}</p>
              )}
            </div>
          </div>
        ))}
      </div>
    </div>
  )
}

export default ObjectAdvantages
