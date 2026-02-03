import { axiosJson } from "@/store/axios";
import { onBeforeUnmount, onMounted, ref } from "vue";

export function useOnlineUsers() {
  const onlineUsers = ref<any[]>([]);
  let interval: any = null;

  const ping = async () => {
    try {
      await axiosJson.post("/online-users/ping");
    } catch (e: any) {7
      console.log("Ping failed", e.response?.status, e.message);
    }
  };

  const fetchOnlineUsers = async () => {
    try {
      const res = await axiosJson.get("/online-users");
      onlineUsers.value = Array.isArray(res.data) ? res.data : [];
    } catch (e: any) {
      console.log("Fetch online users failed", e.response?.status, e.message);
      onlineUsers.value = [];
    }
  };

  onMounted(async () => {
    await ping();
    await fetchOnlineUsers();

    interval = setInterval(async () => {
      await ping();
      await fetchOnlineUsers();
    }, 15000); // every 15 seconds
  });

  onBeforeUnmount(() => {
    if (interval) clearInterval(interval);
  });

  return { onlineUsers };
}
