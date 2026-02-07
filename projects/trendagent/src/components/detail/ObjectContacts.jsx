import './ObjectContacts.css'

const ObjectContacts = ({ contactsData }) => {
  if (!contactsData || !contactsData.data) {
    return null
  }

  const contacts = Array.isArray(contactsData.data) ? contactsData.data : [contactsData.data]

  if (contacts.length === 0) {
    return null
  }

  return (
    <div className="object-contacts">
      <h2>Контакты</h2>
      <div className="contacts-content">
        {contacts.map((contact, index) => (
          <div key={index} className="contact-item">
            {contact.name && <div className="contact-name">{contact.name}</div>}
            {contact.phone && (
              <div className="contact-phone">
                <a href={`tel:${contact.phone}`}>{contact.phone}</a>
              </div>
            )}
            {contact.email && (
              <div className="contact-email">
                <a href={`mailto:${contact.email}`}>{contact.email}</a>
              </div>
            )}
            {contact.position && <div className="contact-position">{contact.position}</div>}
          </div>
        ))}
      </div>
    </div>
  )
}

export default ObjectContacts
