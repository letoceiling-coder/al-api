import './FlatBlockInfo.css'

const FlatBlockInfo = ({ block }) => {
  if (!block) return null

  const formatDate = (date) => {
    if (!date) return '—'
    try {
      const d = new Date(date)
      return d.toLocaleDateString('ru-RU', { year: 'numeric', month: 'long', day: 'numeric' })
    } catch {
      return date
    }
  }

  const deadline = block.deadline || block.deadline_date || '—'
  const keyTransfer = block.key_transfer || block.key_transfer_date || '—'
  const builder = block.builder_name || block.builder?.name || '—'
  const propertyClass = block.property_class || block.class || '—'
  const building = block.building_name || block.building || block.corpus || '—'
  const section = block.section_name || block.section || '—'
  const houseType = block.house_type || block.type || '—'
  const facade = block.facade || block.facade_type || '—'
  const parking = block.parking || block.parking_type || '—'
  const elevator = block.elevator || block.elevator_type || '—'
  const ceilingHeight = block.ceiling_height || block.ceiling || '—'
  const contract = block.contract_type || block.contract || '—'
  const payment = block.payment_type || block.payment || '—'
  const escrow = block.escrow || block.escrow_available || false

  return (
    <div className="apartment-passport">
      {deadline !== '—' && (
        <div className="apartment-passport__row row">
          <div className="col-auto">Срок сдачи</div>
          <div className="col-auto"><span>{formatDate(deadline)}</span></div>
        </div>
      )}
      
      {keyTransfer !== '—' && (
        <div className="apartment-passport__row row">
          <div className="col-auto">Срок передачи ключей</div>
          <div className="col-auto"><span>{keyTransfer}</span></div>
        </div>
      )}
      
      {builder !== '—' && (
        <div className="apartment-passport__row row">
          <div className="col-auto">Застройщик</div>
          <div className="col-auto"><span>{builder}</span></div>
        </div>
      )}
      
      {propertyClass !== '—' && (
        <div className="apartment-passport__row row">
          <div className="col-auto">Класс недвижимости</div>
          <div className="col-auto"><span>{propertyClass}</span></div>
        </div>
      )}
      
      {building !== '—' && (
        <div className="apartment-passport__row row">
          <div className="col-auto">Корпус</div>
          <div className="col-auto"><span>{building}</span></div>
        </div>
      )}
      
      {section !== '—' && (
        <div className="apartment-passport__row row">
          <div className="col-auto">Секция</div>
          <div className="col-auto"><span>{section}</span></div>
        </div>
      )}
      
      {houseType !== '—' && (
        <div className="apartment-passport__row row">
          <div className="col-auto">Тип дома</div>
          <div className="col-auto"><span>{houseType}</span></div>
        </div>
      )}
      
      {facade !== '—' && (
        <div className="apartment-passport__row row">
          <div className="col-auto">Фасад</div>
          <div className="col-auto"><span>{facade}</span></div>
        </div>
      )}
      
      {parking !== '—' && (
        <div className="apartment-passport__row row">
          <div className="col-auto">Паркинг</div>
          <div className="col-auto"><span>{parking}</span></div>
        </div>
      )}
      
      {elevator !== '—' && (
        <div className="apartment-passport__row row">
          <div className="col-auto">Лифт</div>
          <div className="col-auto"><span>{elevator}</span></div>
        </div>
      )}
      
      {ceilingHeight !== '—' && (
        <div className="apartment-passport__row row">
          <div className="col-auto">Высота потолков</div>
          <div className="col-auto"><span>{ceilingHeight} м</span></div>
        </div>
      )}
      
      {contract !== '—' && (
        <div className="apartment-passport__row row">
          <div className="col-auto">Договор</div>
          <div className="col-auto"><span>{contract}</span></div>
        </div>
      )}
      
      {payment !== '—' && (
        <div className="apartment-passport__row row">
          <div className="col-auto">Оплата</div>
          <div className="col-auto"><span>{payment}</span></div>
        </div>
      )}
      
      <div className="apartment-passport__row row">
        <div className="col-auto">Эскроу</div>
        <div className="col-auto"><span>{escrow ? 'Да' : 'Нет'}</span></div>
      </div>
    </div>
  )
}

export default FlatBlockInfo
