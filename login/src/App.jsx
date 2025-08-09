import React, { useState } from "react";
import "./Login.css";

export default function App() {
  const [step, setStep] = useState(1); // 1 = username, 2 = OTP
  const [username, setUsername] = useState("");
  const [otp, setOtp] = useState("");
  const [honeypot, setHoneypot] = useState("");
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState("");

const API_BASE = "http://localhost/home-test/otp.php";

  const handleUsernameSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setMessage("");

    try {
      const formData = new FormData();
      formData.append("username", username);
      formData.append("honeypot", honeypot);

      const res = await fetch(`${API_BASE}?data=request_otp`, {
        method: "POST",
        body: formData,
      });
      console.log(res)
      const data = await res.json();

      if (data.status === "otp_sent") {
        setStep(2);
        setMessage("OTP sent to your email. Please check.");
      } else {
        setMessage(data.error || "Error requesting OTP");
      }
    } catch (err) {
      console.log(err)
      setMessage("Network error");
    } finally {
      setLoading(false);
    }
  };

  const handleOtpSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setMessage("");

    try {
      const formData = new FormData();
      formData.append("username", username);
      formData.append("otp", otp);

      const res = await fetch(`${API_BASE}?data=verify_otp`, {
        method: "POST",
        body: formData,
      });
      const data = await res.json();

      if (data.status === "success" && data.token) {
        localStorage.setItem("auth_token", data.token);
        // storing username to login (not default user)
        // localStorage.setItem("username", username); 
        window.location.href = `http://localhost/home-test/index.php?username=${encodeURIComponent(username)}`;
        // window.location.href = "http://localhost/home-test/index.php"; // Redirect to main app
      } else {
        setMessage(data.error || "Invalid OTP");
      }
    } catch (err) {
      setMessage("Network error");
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="login-container">
      {step === 1 && (
        <form onSubmit={handleUsernameSubmit}>
          <h2>Welcome to Itamar - Whisp</h2>
          <h2>Login</h2>
          <input
            type="text"
            placeholder="Enter username"
            value={username}
            onChange={(e) => setUsername(e.target.value)}
            required
          />
          {/* Honeypot */}
          <input
            type="text"
            name="honeypot"
            style={{ display: "none" }}
            value={honeypot}
            onChange={(e) => setHoneypot(e.target.value)}
          />
          <button type="submit" disabled={loading}>
            {loading ? "Sending..." : "Request OTP"}
          </button>
          {message && <p className="msg">{message}</p>}
        </form>
      )}

      {step === 2 && (
        <form onSubmit={handleOtpSubmit}>
          <h2>Enter OTP</h2>
          <input
            type="text"
            placeholder="Enter OTP"
            value={otp}
            onChange={(e) => setOtp(e.target.value)}
            required
          />
          <button type="submit" disabled={loading}>
            {loading ? "Verifying..." : "Verify OTP"}
          </button>
          {message && <p className="msg">{message}</p>}
        </form>
      )}
    </div>
  );
}
