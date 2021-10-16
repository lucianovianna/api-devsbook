# Devsbook API

## Endpoints

* acessivel sem login

POST *api/auth/login (email, password)
POST api/auth/logout
POST api/auth/refresh

POST *api/user (name, email, password, birthdate)
PUT api/user (name, email, birthdate, city, work, password, password_confirm)

POST api/user/avatar (avatar)
POST api/user/cover (cover)

GET api/feed (page)
GET api/user/feed (page)
GET api/user/:id/feed (page)

GET api/user
GET api/user/:id

POST api/feed (type=text/photo, body, photo)

POST api/post/:id/like
POST api/post/:id/comment (txt)

GET api/search (txt)






