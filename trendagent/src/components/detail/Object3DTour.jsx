import './Object3DTour.css'

const Object3DTour = ({ tourData }) => {
  if (!tourData || !tourData.data) {
    return null
  }

  const tour = tourData.data

  if (!tour.url && !tour.iframe_url) {
    return null
  }

  return (
    <div className="object-3d-tour">
      <h2>3D-тур / Аэропанорама</h2>
      <div className="tour-content">
        {tour.iframe_url ? (
          <iframe
            src={tour.iframe_url}
            className="tour-iframe"
            allowFullScreen
            title="3D тур"
          />
        ) : tour.url ? (
          <div className="tour-link">
            <a href={tour.url} target="_blank" rel="noopener noreferrer" className="tour-button">
              Открыть 3D-тур
            </a>
          </div>
        ) : null}
        {tour.description && <div className="tour-description">{tour.description}</div>}
      </div>
    </div>
  )
}

export default Object3DTour
