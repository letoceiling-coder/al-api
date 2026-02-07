import './ObjectDiscounts.css'

const ObjectDiscounts = ({ discountsData }) => {
  if (!discountsData || !discountsData.data) {
    return null
  }

  const discounts = Array.isArray(discountsData.data) ? discountsData.data : [discountsData.data]

  if (discounts.length === 0) {
    return null
  }

  return (
    <div className="object-discounts">
      <h2>Акции и скидки</h2>
      <div className="discounts-content">
        {discounts.map((discount, index) => (
          <div key={index} className="discount-item">
            {discount.name && <div className="discount-name">{discount.name}</div>}
            {discount.description && <div className="discount-description">{discount.description}</div>}
            {discount.value && <div className="discount-value">{discount.value}</div>}
            {discount.valid_until && (
              <div className="discount-valid-until">Действует до: {discount.valid_until}</div>
            )}
          </div>
        ))}
      </div>
    </div>
  )
}

export default ObjectDiscounts
