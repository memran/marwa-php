# 🚀 MarwaPHP Docker Compose Tutorial

This tutorial explains how to launch your MarwaPHP stack in development or production using Docker Compose.

---

## 🧱 Step 1: Directory Structure

```bash
your-project/
├── docker-compose.yml
├── launch.sh
├── nginx/
│   └── conf.d/
│       └── default.conf
├── src/              # Your MarwaPHP project root
├── .env.dev
└── .env.prod
```

---

## ⚙️ Step 2: Configuration Files

Create `.env.dev` and `.env.prod` for your environments.

**.env.dev**
```dotenv
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost
JWT_SECRET=devsecret
```

**.env.prod**
```dotenv
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com
JWT_SECRET=prodsecret
```

---

## 🚦 Step 3: Launch Your Environment

### For Development:
```bash
./launch.sh dev
```

### For Production:
```bash
./launch.sh prod
```

---

## 📁 Step 4: Codebase & Storage

- Mount your PHP app under `./src`
- Nginx will serve from `public/`
- MySQL and Redis use named volumes to ensure **data persistence**
- Kafka is included for advanced messaging and analytics

---

## 🛑 Stopping Services

```bash
docker-compose down
```

To remove volumes:

```bash
docker-compose down -v
```

---

## 🔄 Update MarwaPHP Code

To pull latest code or updates from your repo:

```bash
cd src/
git pull origin main
```

Then rebuild if needed:

```bash
./launch.sh dev
```

---

## 🎉 Done!

You now have a scalable, containerized MarwaPHP dev/prod environment ready for modern application development.

