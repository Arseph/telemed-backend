<template>
  <div>
    <div class="video-container">
      <div class="video-wrap">
        <video ref="remoteVideo" autoplay playsinline class="remote-video" />
        <video ref="localVideo" autoplay playsinline muted class="local-video" />
      </div>
    </div>



    <div class="device-selectors">
      <VMenu location="top">
        <template #activator="{ props }">
          <VBtn
            v-if="videoEnabled"
            v-bind="props"
            variant="tonal"
            icon="tabler-video"
            color="warning"
          />
          <VBtn
            v-else
            v-bind="props"
            variant="tonal"
            icon="tabler-video-off"
            color="error"
          />
        </template>

        <VList>
          <VListItem
            v-for="cam in cameras"
            :key="cam.deviceId"
            @click="selecVid(cam.deviceId)"
          >
            <VListItemTitle>
              <VBtn
                v-if="selectedCamera == cam.deviceId"
                icon="tabler-check"
                variant="text"
                color="error"
              />
              {{ cam.label || 'Camera ' + cam.deviceId }}
            </VListItemTitle>
          </VListItem>
          <VListItem
            @click="toggleVideo"
          >
            <VListItemTitle>
              {{ videoEnabled ? 'Turn Off Camera' : 'Turn On Camera' }}
            </VListItemTitle>
          </VListItem>
        </VList>
      </VMenu>
      <VMenu location="top">
        <template #activator="{ props }">
          <VBtn
            v-if="audioEnabled"
            v-bind="props"
            variant="tonal"
            icon="tabler-microphone"
          />
          <VBtn
            v-else
            v-bind="props"
            variant="tonal"
            icon="tabler-microphone-off"
            color="error"
          />
        </template>

        <VList>
          <VListItem
            v-for="mic in microphones"
            :key="mic.deviceId"
            @click="selectMic(mic.deviceId)"
          >
            <VListItemTitle>
               <VBtn
                v-if="selectedMic == mic.deviceId"
                icon="tabler-check"
                variant="text"
                color="error"
              />
              {{ mic.label || 'Mic ' + mic.deviceId }}
            </VListItemTitle>
          </VListItem>
          <VListItem
            @click="toggleAudio"
          >
            <VListItemTitle>
              {{ audioEnabled ? 'Mute Mic' : 'Unmute Mic' }}
            </VListItemTitle>
          </VListItem>
        </VList>
      </VMenu>
    </div>

    <div class="actions">
      <button @click="startCall">Start Call</button>
      <button @click="startRecording">Record</button>
      <button @click="stopRecording">Stop & Save</button>
    </div>
  </div>
</template>


<script lang="ts" setup>
import { io } from 'socket.io-client'
import { onBeforeUnmount, onMounted, ref } from 'vue'

const socket = io('http://127.0.0.1:3000')

const localVideo = ref<HTMLVideoElement | null>(null)
const remoteVideo = ref<HTMLVideoElement | null>(null)

const selectedCamera = ref<string | null>(null)
const selectedMic = ref<string | null>(null)
const cameras = ref<MediaDeviceInfo[]>([])
const microphones = ref<MediaDeviceInfo[]>([])
const videoEnabled = ref(true)
const audioEnabled = ref(true)
let localStream: MediaStream
let peerConnection: RTCPeerConnection
let mediaRecorder: MediaRecorder
let recordedChunks: Blob[] = []

const servers = {
  iceServers: [{ urls: 'stun:stun.l.google.com:19302' }]
}

const getStream = async () => {
  const constraints = {
    video: selectedCamera.value ? { deviceId: { exact: selectedCamera.value } } : true,
    audio: selectedMic.value ? { deviceId: { exact: selectedMic.value } } : true
  }
  return await navigator.mediaDevices.getUserMedia(constraints)
}

const restartStream = async () => {
  if (localStream) {
    localStream.getTracks().forEach(track => track.stop())
  }

  localStream = await getStream()

  if (localVideo.value) {
    localVideo.value.srcObject = localStream
  }

  // Replace tracks in peer connection
  if (peerConnection) {
    const senders = peerConnection.getSenders()
    localStream.getTracks().forEach(track => {
      const sender = senders.find(s => s.track?.kind === track.kind)
      if (sender) {
        sender.replaceTrack(track)
      }
    })
  }
}

const startCall = async () => {
  localStream = await getStream()
  if (localVideo.value) localVideo.value.srcObject = localStream

  peerConnection = new RTCPeerConnection(servers)
  localStream.getTracks().forEach(track => peerConnection.addTrack(track, localStream))

  peerConnection.ontrack = (event) => {
    if (remoteVideo.value) remoteVideo.value.srcObject = event.streams[0]
  }

  peerConnection.onicecandidate = (event) => {
    if (event.candidate) socket.emit('ice-candidate', event.candidate)
  }

  const offer = await peerConnection.createOffer()
  await peerConnection.setLocalDescription(offer)
  socket.emit('offer', offer)
}
const toggleVideo = () => {
  if (!localStream) return
  videoEnabled.value = !videoEnabled.value
  localStream.getVideoTracks().forEach(track => (track.enabled = videoEnabled.value))
}

const toggleAudio = () => {
  if (!localStream) return
  audioEnabled.value = !audioEnabled.value
  localStream.getAudioTracks().forEach(track => (track.enabled = audioEnabled.value))
}

const loadDevices = async () => {
  const devices = await navigator.mediaDevices.enumerateDevices()
  cameras.value = devices.filter(d => d.kind === 'videoinput')
  microphones.value = devices.filter(d => d.kind === 'audioinput')

  if (!selectedCamera.value && cameras.value.length) {
    selectedCamera.value = cameras.value[0].deviceId
  }

  if (!selectedMic.value && microphones.value.length) {
    selectedMic.value = microphones.value[0].deviceId
  }
}

onMounted(async () => {
  await loadDevices()
  await restartStream()
  socket.on('offer', async (offer) => {
    localStream = await navigator.mediaDevices.getUserMedia({ video: true, audio: true })
    if (localVideo.value) localVideo.value.srcObject = localStream

    peerConnection = new RTCPeerConnection(servers)
    localStream.getTracks().forEach(track => peerConnection.addTrack(track, localStream))

    peerConnection.ontrack = (event) => {
      if (remoteVideo.value) remoteVideo.value.srcObject = event.streams[0]
    }

    peerConnection.onicecandidate = (event) => {
      if (event.candidate) socket.emit('ice-candidate', event.candidate)
    }

    await peerConnection.setRemoteDescription(new RTCSessionDescription(offer))
    const answer = await peerConnection.createAnswer()
    await peerConnection.setLocalDescription(answer)
    socket.emit('answer', answer)
  })

  socket.on('answer', async (answer) => {
    await peerConnection.setRemoteDescription(new RTCSessionDescription(answer))
  })

  socket.on('ice-candidate', async (candidate) => {
    if (peerConnection) {
      await peerConnection.addIceCandidate(new RTCIceCandidate(candidate))
    }
  })
})

onBeforeUnmount(() => {
  socket.disconnect()
})

const canvas = document.createElement('canvas')
const ctx = canvas.getContext('2d')
let canvasStream: MediaStream

const startRecording = () => {
  if (!localVideo.value || !remoteVideo.value) return

  // Set canvas size to match video layout
  canvas.width = 1280
  canvas.height = 480

  // Draw both videos on canvas every frame
  const drawFrame = () => {
    if (!ctx) return
    ctx.drawImage(localVideo.value!, 0, 0, 640, 480) // Left half
    ctx.drawImage(remoteVideo.value!, 640, 0, 640, 480) // Right half
    requestAnimationFrame(drawFrame)
  }
  drawFrame()

  // Capture canvas as stream
  canvasStream = (canvas as HTMLCanvasElement).captureStream(30) // 30 fps

  // Add local audio to the stream
  localStream.getAudioTracks().forEach(track => {
    canvasStream.addTrack(track)
  })

  recordedChunks = []
  mediaRecorder = new MediaRecorder(canvasStream)

  mediaRecorder.ondataavailable = (event) => {
    if (event.data.size > 0) recordedChunks.push(event.data)
  }

  mediaRecorder.start()
}


const stopRecording = () => {
  if (mediaRecorder && mediaRecorder.state !== 'inactive') {
    mediaRecorder.stop()

    mediaRecorder.onstop = () => {
      const blob = new Blob(recordedChunks, { type: 'video/webm' })
      const url = URL.createObjectURL(blob)

      // Auto-download
      const a = document.createElement('a')
      a.href = url
      a.download = 'video-conference.webm'
      a.click()

      URL.revokeObjectURL(url)
    }
  }
}
function selectMic(deviceId: string) {
  selectedMic.value = deviceId
  restartStream()
}
function selecVid(deviceId: string) {
  selectedCamera.value = deviceId
  restartStream()
}
</script>

<style scoped>
.video-container {
  display: flex;
  justify-content: center;
  align-items: center;
}
.video-wrap {
  position: relative;
  width: 100%;
  max-width: 1280px;
  height: 520px;
  background: #000;
  overflow: hidden;
  border-radius: 12px;
}

.remote-video {
  width: 100%;
  height: 100%;
  object-fit: cover;
}

.local-video {
  position: absolute;
  bottom: 1rem;
  right: 1rem;
  width: 20%;
  max-width: 240px;
  aspect-ratio: 16 / 9;
  border: 2px solid white;
  border-radius: 8px;
  object-fit: cover;
  box-shadow: 0 0 10px rgba(0,0,0,0.6);
}
@media (max-width: 768px) {
  .local-video {
    width: 30%;
    max-width: 120px;
    bottom: 0.5rem;
    right: 0.5rem;
    height:140px;
  }

  .video-wrap {
    aspect-ratio: auto;
    height: 500px;
  }
}
</style>
