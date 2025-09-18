const express = require('express')
const http = require('http')
const socketIo = require('socket.io')
const cors = require('cors')

const app = express()
app.use(cors())

const server = http.createServer(app)
const io = socketIo(server, {
  cors: {
    origin: '*',
    methods: ['GET', 'POST']
  }
})

io.on('connection', socket => {
  console.log('Client connected:', socket.id)

  socket.on('offer', data => socket.broadcast.emit('offer', data))
  socket.on('answer', data => socket.broadcast.emit('answer', data))
  socket.on('ice-candidate', data => socket.broadcast.emit('ice-candidate', data))

  socket.on('disconnect', () => console.log('Client disconnected:', socket.id))
})

server.listen(3000, () => {
  console.log('Socket.IO server running on http://localhost:5173')
})
