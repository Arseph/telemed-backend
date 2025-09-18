<script lang="ts">
import * as faceapi from 'face-api.js'
import { defineComponent, onBeforeUnmount, ref, watch } from 'vue'

export default defineComponent({
  props: {
    visible: {
      type: Boolean,
      required: true,
    },
    title: {
      type: String,
      default: 'Confirm Time In',
    },
    message: {
      type: String,
      default: 'Detecting your face for time in...',
    },
  },
  emits: ['confirm'],
  setup(props, { emit }) {
    const modalWebcam = ref<HTMLVideoElement | null>(null)
    const modalOverlay = ref<HTMLCanvasElement | null>(null)
    let stream: MediaStream | null = null
    let intervalId: number | null = null

    const startWebcam = async () => {
      try {
        const constraints = { video: true }

        stream = await navigator.mediaDevices.getUserMedia(constraints)
        if (modalWebcam.value)
          modalWebcam.value.srcObject = stream

        await faceapi.nets.tinyFaceDetector.loadFromUri('/models')
        await faceapi.nets.faceLandmark68Net.loadFromUri('/models')
        await faceapi.nets.faceRecognitionNet.loadFromUri('/models')
      }
      catch (error) {
        console.error('Error starting webcam:', error)
      }
    }

    const stopWebcam = () => {
      if (stream)
        stream.getTracks().forEach(track => track.stop())
    }

    const captureSingleFace = async () => {
      if (modalWebcam.value) {
        const video = modalWebcam.value
        const detection = await faceapi.detectSingleFace(video, new faceapi.TinyFaceDetectorOptions()).withFaceLandmarks().withFaceDescriptor()

        if (detection) {
          emit('confirm', detection.descriptor) // Send face descriptor to parent component
          stopWebcam()
        }
      }
    }

    const startFaceDetection = () => {
      intervalId = window.setInterval(async () => {
        await captureSingleFace()
      }, 1000)
    }

    const stopFaceDetection = () => {
      if (intervalId) {
        clearInterval(intervalId)
        intervalId = null
      }
    }

    watch(() => props.visible, newVal => {
      if (newVal) {
        startWebcam().then(() => {
          startFaceDetection()
        })
      }
      else {
        stopWebcam()
        stopFaceDetection()
      }
    })

    onBeforeUnmount(() => {
      stopWebcam()
      stopFaceDetection()
    })

    return {
      modalWebcam,
      modalOverlay,
    }
  },
})
</script>

<template>
  <VDialog
    v-model="visible"
    width="500"
  >
    <VCard>
      <template #title>
        {{ title }}
      </template>
      <VCardText>
        <p>{{ message }}</p>
      </VCardText>
      <div class="webcam-wrapper">
        <video
          ref="modalWebcam"
          autoplay
          playsinline
        />
        <canvas ref="modalOverlay" />
      </div>
      <VCardText class="d-flex justify-center">
        <p>Processing face detection...</p>
      </VCardText>
    </VCard>
  </VDialog>
</template>

  <style scoped>
.webcam-wrapper {
  position: relative;
  inline-size: 100%;
  margin-block: 0;
  margin-inline: auto;
  max-inline-size: 400px;
}

video,
canvas {
  display: block;
  block-size: auto;
  inline-size: 100%;
}

canvas {
  position: absolute;
  inset-block-start: 0;
  inset-inline-start: 0;
}
  </style>
