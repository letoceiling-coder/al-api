import './ObjectBanks.css'

const ObjectBanks = ({ banksData }) => {
  if (!banksData || !banksData.data) {
    return null
  }

  const banks = Array.isArray(banksData.data) ? banksData.data : [banksData.data]

  if (banks.length === 0) {
    return null
  }

  return (
    <div className="object-banks">
      <h2>Банки эскроу</h2>
      <div className="banks-content">
        {banks.map((bank, index) => (
          <div key={index} className="bank-item">
            {bank.name && <div className="bank-name">{bank.name}</div>}
            {bank.logo && (
              <div className="bank-logo">
                <img src={bank.logo} alt={bank.name || 'Банк'} />
              </div>
            )}
            {bank.description && <div className="bank-description">{bank.description}</div>}
          </div>
        ))}
      </div>
    </div>
  )
}

export default ObjectBanks
