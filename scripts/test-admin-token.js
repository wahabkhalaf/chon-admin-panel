#!/usr/bin/env node
import axios from 'axios';

const baseUrl = process.env.API_BASE_URL || 'http://localhost:3001';
const token = process.env.API_ADMIN_TOKEN;

if (!token) {
  console.error('API_ADMIN_TOKEN env var is required');
  process.exit(1);
}

async function main() {
  try {
    console.log('Base URL:', baseUrl);

    // 1) Health
    const h = await axios.get(`${baseUrl}/api/health`, {
      headers: { Authorization: `Bearer ${token}` },
      timeout: 5000,
    });
    console.log('Health:', h.status, h.data);

    // 2) Send to users
    const payload = {
      userIds: ['1'],
      title: 'Token Test',
      message: 'Testing admin token auth',
      type: 'personal',
      priority: 'low',
      data: { ping: true },
    };

    const r = await axios.post(
      `${baseUrl}/api/v1/notifications/send-to-player`,
      payload,
      { headers: { Authorization: `Bearer ${token}`, 'API-Version': 'v1' }, timeout: 5000 },
    );
    console.log('Send:', r.status, r.data);
  } catch (e) {
    if (e.response) {
      console.error('Error:', e.response.status, e.response.data);
    } else {
      console.error('Error:', e.message);
    }
    process.exit(1);
  }
}

main();


