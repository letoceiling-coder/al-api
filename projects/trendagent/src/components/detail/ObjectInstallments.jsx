import './ObjectInstallments.css'

const ObjectInstallments = ({ installmentsData }) => {
  if (!installmentsData || !installmentsData.data) {
    return null
  }

  const programs = Array.isArray(installmentsData.data) ? installmentsData.data : [installmentsData.data]

  if (programs.length === 0) {
    return null
  }

  return (
    <div className="object-installments">
      <h2>Рассрочка</h2>
      <div className="installments-content">
        {programs.map((program, index) => (
          <div key={index} className="installment-program">
            {program.name && <div className="program-name">{program.name}</div>}
            {program.rate && <div className="program-rate">Ставка: {program.rate}%</div>}
            {program.period && <div className="program-period">Срок: {program.period}</div>}
            {program.description && <div className="program-description">{program.description}</div>}
          </div>
        ))}
      </div>
    </div>
  )
}

export default ObjectInstallments
