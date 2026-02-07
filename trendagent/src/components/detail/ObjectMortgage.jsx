import './ObjectMortgage.css'

const ObjectMortgage = ({ mortgageData }) => {
  if (!mortgageData || !mortgageData.data) {
    return null
  }

  const programs = Array.isArray(mortgageData.data) ? mortgageData.data : [mortgageData.data]

  if (programs.length === 0) {
    return null
  }

  return (
    <div className="object-mortgage">
      <h2>Ипотека</h2>
      <div className="mortgage-content">
        <div className="mortgage-buttons">
          <button className="card-button">
            <div className="card-button-icon">📄</div>
            <div className="card-button-label">Посмотреть программы и ставки</div>
          </button>
          <button className="card-button">
            <div className="card-button-icon">💬</div>
            <div className="card-button-label">Задать вопрос</div>
          </button>
          <button className="card-button">
            <div className="card-button-icon">📝</div>
            <div className="card-button-label">Отправить заявку на ипотеку</div>
          </button>
        </div>
        {programs.length > 0 && (
          <div className="mortgage-programs">
            {programs.map((program, index) => (
              <div key={index} className="mortgage-program">
                {program.name && <div className="program-name">{program.name}</div>}
                {program.rate && <div className="program-rate">Ставка: {program.rate}%</div>}
                {program.description && <div className="program-description">{program.description}</div>}
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  )
}

export default ObjectMortgage
