import './ObjectLocation.css'

const ObjectLocation = ({ address, nearbyPlaces }) => {
  const places = nearbyPlaces || []

  return (
    <div className="object-location card">
      <h2 className="section-title">Расположение</h2>
      
      {address && (
        <div className="location-address">
          <h3 className="subsection-title">Адрес</h3>
          <p className="address-text">{address}</p>
        </div>
      )}

      {places.length > 0 && (
        <div className="nearby-places">
          <h3 className="subsection-title">Места рядом</h3>
          <div className="places-grid">
            {places.map((place, index) => (
              <div key={place._id || index} className="place-item">
                {place.image && (
                  <img
                    src={place.image.url || place.image}
                    alt={place.name || place.title}
                    className="place-image"
                  />
                )}
                <div className="place-content">
                  <h4 className="place-title">{place.name || place.title}</h4>
                  {place.description && (
                    <p className="place-description">{place.description}</p>
                  )}
                  {place.distance && (
                    <span className="place-distance">{place.distance}</span>
                  )}
                </div>
              </div>
            ))}
          </div>
        </div>
      )}
    </div>
  )
}

export default ObjectLocation
