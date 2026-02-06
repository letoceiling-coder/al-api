import './ObjectVideos.css'

const ObjectVideos = ({ videos }) => {
  const videosList = videos || []

  if (!videosList || videosList.length === 0) {
    return (
      <div className="object-videos card">
        <h2 className="section-title">Видео</h2>
        <div className="empty-state">
          <p>Видео отсутствуют</p>
        </div>
      </div>
    )
  }

  return (
    <div className="object-videos card">
      <h2 className="section-title">Видео</h2>
      <div className="videos-grid">
        {videosList.map((video, index) => (
          <div key={video._id || index} className="video-item">
            {video.url && (
              <iframe
                src={video.url}
                title={video.name || video.title || `Видео ${index + 1}`}
                className="video-iframe"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowFullScreen
              />
            )}
            {(video.name || video.title) && (
              <h3 className="video-title">{video.name || video.title}</h3>
            )}
          </div>
        ))}
      </div>
    </div>
  )
}

export default ObjectVideos
