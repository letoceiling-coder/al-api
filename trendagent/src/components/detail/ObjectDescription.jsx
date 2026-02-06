import './ObjectDescription.css'

const ObjectDescription = ({ description }) => {
  if (!description) {
    return (
      <div className="object-description card">
        <h2 className="section-title">Описание</h2>
        <div className="empty-state">
          <p>Описание отсутствует</p>
        </div>
      </div>
    )
  }

  return (
    <div className="object-description card">
      <h2 className="section-title">Описание</h2>
      <div
        className="description-content"
        dangerouslySetInnerHTML={{ __html: description }}
      />
    </div>
  )
}

export default ObjectDescription
