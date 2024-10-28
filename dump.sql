--
-- PostgreSQL database dump
--

-- Dumped from database version 16.0
-- Dumped by pg_dump version 16.0

-- Started on 2024-10-28 10:49:15

SET statement_timeout = 0;
SET lock_timeout = 0;
SET idle_in_transaction_session_timeout = 0;
SET client_encoding = 'UTF8';
SET standard_conforming_strings = on;
SELECT pg_catalog.set_config('search_path', '', false);
SET check_function_bodies = false;
SET xmloption = content;
SET client_min_messages = warning;
SET row_security = off;

--
-- TOC entry 5 (class 2615 OID 20132)
-- Name: public; Type: SCHEMA; Schema: -; Owner: postgres
--

CREATE SCHEMA public;


ALTER SCHEMA public OWNER TO postgres;

--
-- TOC entry 242 (class 1255 OID 20134)
-- Name: delete_client(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.delete_client() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
	DELETE FROM included WHERE id_equip IN (SELECT id_equip FROM client_equip WHERE id_client = OLD.id_client);
    DELETE FROM client_equip WHERE id_client = OLD.id_client;
    DELETE FROM booking WHERE id_client = OLD.id_client;
    RETURN OLD;
END;
$$;


ALTER FUNCTION public.delete_client() OWNER TO postgres;

--
-- TOC entry 258 (class 1255 OID 27884)
-- Name: get_booking_report(refcursor); Type: PROCEDURE; Schema: public; Owner: postgres
--

CREATE PROCEDURE public.get_booking_report(INOUT ref refcursor)
    LANGUAGE plpgsql
    AS $$BEGIN
    OPEN ref FOR
    SELECT
        date_request,
        total_bookings,
        CASE
            WHEN date_request = (SELECT MAX(date_request) FROM booking)
            THEN (
                SELECT SUM(total_bookings)
                FROM (
                    SELECT
                        date_request,
                        COUNT(id_booking) AS total_bookings
                    FROM
                        booking
                    WHERE
                        date_request >= (SELECT MAX(date_request) - 30 FROM booking)
                    GROUP BY
                        date_request
                )
            )
            ELSE NULL
        END AS bookings_last_30_days
    FROM
        (SELECT
            date_request,
            COUNT(id_booking) AS total_bookings
        FROM
            booking
        GROUP BY
            date_request
        )
    ORDER BY
        date_request;
END;
$$;


ALTER PROCEDURE public.get_booking_report(INOUT ref refcursor) OWNER TO postgres;

--
-- TOC entry 250 (class 1255 OID 27886)
-- Name: get_client_equip_report(refcursor); Type: PROCEDURE; Schema: public; Owner: postgres
--

CREATE PROCEDURE public.get_client_equip_report(INOUT ref refcursor)
    LANGUAGE plpgsql
    AS $$
BEGIN
    OPEN ref FOR
SELECT 
        c.fn_client AS client_name,
        ce.name_equip AS equipment_name,
        ce.description_equip AS equipment_description,
        w.fn_worker AS worker_name  -- Имя работника может быть NULL
    FROM 
        client_equip ce
    JOIN 
        client c ON ce.id_client = c.id_client
    JOIN 
        included i ON ce.id_equip = i.id_equip
    JOIN 
        booking b ON i.id_booking = b.id_booking
    LEFT JOIN 
        provided_service ps ON b.id_booking = ps.id_booking  -- Левое соединение, чтобы включить все заказы
    LEFT JOIN 
        worker w ON ps.id_worker = w.id_worker;  -- Левое соединение для работника
END;
$$;


ALTER PROCEDURE public.get_client_equip_report(INOUT ref refcursor) OWNER TO postgres;

--
-- TOC entry 244 (class 1255 OID 27883)
-- Name: get_service_revenue(refcursor); Type: PROCEDURE; Schema: public; Owner: postgres
--

CREATE PROCEDURE public.get_service_revenue(INOUT ref refcursor)
    LANGUAGE plpgsql
    AS $$
BEGIN
    OPEN ref FOR
    SELECT
        s.name_service,
        SUM(ps.amount_service_provided * s.price_service) AS total_revenue
    FROM
        provided_service ps
    JOIN
        service s ON ps.id_service = s.id_service
    WHERE
        ps.id_booking IS NOT NULL
        AND ps.id_worker IS NOT NULL
        AND ps.id_service IS NOT NULL
    GROUP BY ROLLUP(s.name_service);
END;
$$;


ALTER PROCEDURE public.get_service_revenue(INOUT ref refcursor) OWNER TO postgres;

--
-- TOC entry 245 (class 1255 OID 27885)
-- Name: get_stock_report(refcursor); Type: PROCEDURE; Schema: public; Owner: postgres
--

CREATE PROCEDURE public.get_stock_report(INOUT ref refcursor)
    LANGUAGE plpgsql
    AS $$
BEGIN
    OPEN ref FOR
    SELECT d.name_detail, d.amount_detail AS current_stock FROM details d;
END;
$$;


ALTER PROCEDURE public.get_stock_report(INOUT ref refcursor) OWNER TO postgres;

--
-- TOC entry 243 (class 1255 OID 20135)
-- Name: update_stock_details(); Type: FUNCTION; Schema: public; Owner: postgres
--

CREATE FUNCTION public.update_stock_details() RETURNS trigger
    LANGUAGE plpgsql
    AS $$
BEGIN
UPDATE details
SET amount_detail = amount_detail - NEW.amount_used_detail
WHERE id_detail = NEW.id_detail;
RETURN NEW;
END;
$$;


ALTER FUNCTION public.update_stock_details() OWNER TO postgres;

SET default_tablespace = '';

SET default_table_access_method = heap;

--
-- TOC entry 215 (class 1259 OID 20136)
-- Name: booking; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.booking (
    id_booking integer NOT NULL,
    id_manager integer,
    date_request date NOT NULL,
    status_booking character varying(20),
    booking_close_date date
);


ALTER TABLE public.booking OWNER TO postgres;

--
-- TOC entry 216 (class 1259 OID 20139)
-- Name: booking_id_booking_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.booking_id_booking_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.booking_id_booking_seq OWNER TO postgres;

--
-- TOC entry 5013 (class 0 OID 0)
-- Dependencies: 216
-- Name: booking_id_booking_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.booking_id_booking_seq OWNED BY public.booking.id_booking;


--
-- TOC entry 239 (class 1259 OID 27868)
-- Name: calls; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.calls (
    id_call integer NOT NULL,
    call_date timestamp without time zone NOT NULL,
    number_call character varying(15) NOT NULL,
    fn_call character varying(70) NOT NULL,
    way_contact character varying(15) NOT NULL,
    id_manager integer
);


ALTER TABLE public.calls OWNER TO postgres;

--
-- TOC entry 238 (class 1259 OID 27867)
-- Name: calls_id_call_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.calls_id_call_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.calls_id_call_seq OWNER TO postgres;

--
-- TOC entry 5014 (class 0 OID 0)
-- Dependencies: 238
-- Name: calls_id_call_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.calls_id_call_seq OWNED BY public.calls.id_call;


--
-- TOC entry 217 (class 1259 OID 20140)
-- Name: client; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.client (
    fn_client character varying(70) NOT NULL,
    number_client character varying(15) NOT NULL,
    id_client integer NOT NULL,
    id_user integer
);


ALTER TABLE public.client OWNER TO postgres;

--
-- TOC entry 218 (class 1259 OID 20143)
-- Name: client_equip; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.client_equip (
    id_equip integer NOT NULL,
    id_client integer,
    name_equip character varying(50),
    sn_equip character varying(30),
    description_equip text
);


ALTER TABLE public.client_equip OWNER TO postgres;

--
-- TOC entry 219 (class 1259 OID 20148)
-- Name: client_equip_id_equip_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.client_equip_id_equip_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.client_equip_id_equip_seq OWNER TO postgres;

--
-- TOC entry 5015 (class 0 OID 0)
-- Dependencies: 219
-- Name: client_equip_id_equip_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.client_equip_id_equip_seq OWNED BY public.client_equip.id_equip;


--
-- TOC entry 220 (class 1259 OID 20149)
-- Name: client_id_client_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.client_id_client_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.client_id_client_seq OWNER TO postgres;

--
-- TOC entry 5016 (class 0 OID 0)
-- Dependencies: 220
-- Name: client_id_client_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.client_id_client_seq OWNED BY public.client.id_client;


--
-- TOC entry 221 (class 1259 OID 20150)
-- Name: details; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.details (
    id_detail bigint NOT NULL,
    amount_detail integer NOT NULL,
    name_detail character varying(100) NOT NULL,
    price_detail numeric NOT NULL
);


ALTER TABLE public.details OWNER TO postgres;

--
-- TOC entry 222 (class 1259 OID 20155)
-- Name: details_id_detai_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.details_id_detai_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.details_id_detai_seq OWNER TO postgres;

--
-- TOC entry 5017 (class 0 OID 0)
-- Dependencies: 222
-- Name: details_id_detai_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.details_id_detai_seq OWNED BY public.details.id_detail;


--
-- TOC entry 223 (class 1259 OID 20156)
-- Name: included; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.included (
    id_booking integer NOT NULL,
    id_equip integer NOT NULL
);


ALTER TABLE public.included OWNER TO postgres;

--
-- TOC entry 224 (class 1259 OID 20159)
-- Name: manager; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.manager (
    id_manager bigint NOT NULL,
    fn_managed character varying(70) NOT NULL,
    number_managed character varying(15) NOT NULL,
    birth_manager date NOT NULL,
    status_manager character varying(30)
);


ALTER TABLE public.manager OWNER TO postgres;

--
-- TOC entry 225 (class 1259 OID 20162)
-- Name: manager_id_manager_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.manager_id_manager_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.manager_id_manager_seq OWNER TO postgres;

--
-- TOC entry 5018 (class 0 OID 0)
-- Dependencies: 225
-- Name: manager_id_manager_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.manager_id_manager_seq OWNED BY public.manager.id_manager;


--
-- TOC entry 241 (class 1259 OID 27899)
-- Name: ordered_calls; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW public.ordered_calls AS
 SELECT c.call_date,
    c.number_call,
    c.fn_call,
    c.way_contact,
    m.fn_managed
   FROM (public.calls c
     LEFT JOIN public.manager m ON ((c.id_manager = m.id_manager)))
  ORDER BY c.call_date DESC;


ALTER VIEW public.ordered_calls OWNER TO postgres;

--
-- TOC entry 228 (class 1259 OID 20167)
-- Name: service; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.service (
    id_service integer NOT NULL,
    name_service character varying(50) NOT NULL,
    price_service money NOT NULL,
    description_service text
);


ALTER TABLE public.service OWNER TO postgres;

--
-- TOC entry 240 (class 1259 OID 27891)
-- Name: ordered_service; Type: VIEW; Schema: public; Owner: postgres
--

CREATE VIEW public.ordered_service AS
 SELECT name_service,
    price_service,
    description_service
   FROM public.service
  ORDER BY name_service;


ALTER VIEW public.ordered_service OWNER TO postgres;

--
-- TOC entry 226 (class 1259 OID 20163)
-- Name: provided_service; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.provided_service (
    amount_service_provided integer,
    id_provided_service integer NOT NULL,
    id_service integer,
    id_worker integer,
    id_booking integer
);


ALTER TABLE public.provided_service OWNER TO postgres;

--
-- TOC entry 227 (class 1259 OID 20166)
-- Name: provided_service_od_provoded_service_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.provided_service_od_provoded_service_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.provided_service_od_provoded_service_seq OWNER TO postgres;

--
-- TOC entry 5019 (class 0 OID 0)
-- Dependencies: 227
-- Name: provided_service_od_provoded_service_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.provided_service_od_provoded_service_seq OWNED BY public.provided_service.id_provided_service;


--
-- TOC entry 237 (class 1259 OID 20421)
-- Name: reviews; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.reviews (
    id_review integer NOT NULL,
    id_booking integer,
    text_review text,
    rating integer
);


ALTER TABLE public.reviews OWNER TO postgres;

--
-- TOC entry 236 (class 1259 OID 20420)
-- Name: reviews_id_review_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.reviews_id_review_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.reviews_id_review_seq OWNER TO postgres;

--
-- TOC entry 5020 (class 0 OID 0)
-- Dependencies: 236
-- Name: reviews_id_review_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.reviews_id_review_seq OWNED BY public.reviews.id_review;


--
-- TOC entry 229 (class 1259 OID 20170)
-- Name: service_id_service_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.service_id_service_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.service_id_service_seq OWNER TO postgres;

--
-- TOC entry 5021 (class 0 OID 0)
-- Dependencies: 229
-- Name: service_id_service_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.service_id_service_seq OWNED BY public.service.id_service;


--
-- TOC entry 230 (class 1259 OID 20171)
-- Name: used_detail; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.used_detail (
    id_used_detail integer NOT NULL,
    id_detail integer,
    id_booking integer,
    amount_used_detail integer
);


ALTER TABLE public.used_detail OWNER TO postgres;

--
-- TOC entry 231 (class 1259 OID 20174)
-- Name: used_detail_id_used_detail_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.used_detail_id_used_detail_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.used_detail_id_used_detail_seq OWNER TO postgres;

--
-- TOC entry 5022 (class 0 OID 0)
-- Dependencies: 231
-- Name: used_detail_id_used_detail_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.used_detail_id_used_detail_seq OWNED BY public.used_detail.id_used_detail;


--
-- TOC entry 232 (class 1259 OID 20175)
-- Name: users; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.users (
    id_user integer NOT NULL,
    username character varying(255),
    password character varying(255),
    email character varying(255),
    role character varying(255) DEFAULT 'client'::character varying
);


ALTER TABLE public.users OWNER TO postgres;

--
-- TOC entry 233 (class 1259 OID 20181)
-- Name: user_id_user_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.user_id_user_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.user_id_user_seq OWNER TO postgres;

--
-- TOC entry 5023 (class 0 OID 0)
-- Dependencies: 233
-- Name: user_id_user_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.user_id_user_seq OWNED BY public.users.id_user;


--
-- TOC entry 234 (class 1259 OID 20182)
-- Name: worker; Type: TABLE; Schema: public; Owner: postgres
--

CREATE TABLE public.worker (
    id_worker bigint NOT NULL,
    fn_worker character varying(70) NOT NULL,
    number_worker character varying(15) NOT NULL,
    birth_worker date NOT NULL,
    status_worker character varying(50)
);


ALTER TABLE public.worker OWNER TO postgres;

--
-- TOC entry 235 (class 1259 OID 20185)
-- Name: worker_id_worker_seq; Type: SEQUENCE; Schema: public; Owner: postgres
--

CREATE SEQUENCE public.worker_id_worker_seq
    AS integer
    START WITH 1
    INCREMENT BY 1
    NO MINVALUE
    NO MAXVALUE
    CACHE 1;


ALTER SEQUENCE public.worker_id_worker_seq OWNER TO postgres;

--
-- TOC entry 5024 (class 0 OID 0)
-- Dependencies: 235
-- Name: worker_id_worker_seq; Type: SEQUENCE OWNED BY; Schema: public; Owner: postgres
--

ALTER SEQUENCE public.worker_id_worker_seq OWNED BY public.worker.id_worker;


--
-- TOC entry 4761 (class 2604 OID 20186)
-- Name: booking id_booking; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.booking ALTER COLUMN id_booking SET DEFAULT nextval('public.booking_id_booking_seq'::regclass);


--
-- TOC entry 4773 (class 2604 OID 27871)
-- Name: calls id_call; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.calls ALTER COLUMN id_call SET DEFAULT nextval('public.calls_id_call_seq'::regclass);


--
-- TOC entry 4762 (class 2604 OID 20187)
-- Name: client id_client; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.client ALTER COLUMN id_client SET DEFAULT nextval('public.client_id_client_seq'::regclass);


--
-- TOC entry 4763 (class 2604 OID 20188)
-- Name: client_equip id_equip; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.client_equip ALTER COLUMN id_equip SET DEFAULT nextval('public.client_equip_id_equip_seq'::regclass);


--
-- TOC entry 4764 (class 2604 OID 20189)
-- Name: details id_detail; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.details ALTER COLUMN id_detail SET DEFAULT nextval('public.details_id_detai_seq'::regclass);


--
-- TOC entry 4765 (class 2604 OID 20190)
-- Name: manager id_manager; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.manager ALTER COLUMN id_manager SET DEFAULT nextval('public.manager_id_manager_seq'::regclass);


--
-- TOC entry 4766 (class 2604 OID 20191)
-- Name: provided_service id_provided_service; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.provided_service ALTER COLUMN id_provided_service SET DEFAULT nextval('public.provided_service_od_provoded_service_seq'::regclass);


--
-- TOC entry 4772 (class 2604 OID 20424)
-- Name: reviews id_review; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.reviews ALTER COLUMN id_review SET DEFAULT nextval('public.reviews_id_review_seq'::regclass);


--
-- TOC entry 4767 (class 2604 OID 20192)
-- Name: service id_service; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.service ALTER COLUMN id_service SET DEFAULT nextval('public.service_id_service_seq'::regclass);


--
-- TOC entry 4768 (class 2604 OID 20193)
-- Name: used_detail id_used_detail; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.used_detail ALTER COLUMN id_used_detail SET DEFAULT nextval('public.used_detail_id_used_detail_seq'::regclass);


--
-- TOC entry 4769 (class 2604 OID 20194)
-- Name: users id_user; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users ALTER COLUMN id_user SET DEFAULT nextval('public.user_id_user_seq'::regclass);


--
-- TOC entry 4771 (class 2604 OID 20195)
-- Name: worker id_worker; Type: DEFAULT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.worker ALTER COLUMN id_worker SET DEFAULT nextval('public.worker_id_worker_seq'::regclass);


--
-- TOC entry 4982 (class 0 OID 20136)
-- Dependencies: 215
-- Data for Name: booking; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.booking (id_booking, id_manager, date_request, status_booking, booking_close_date) FROM stdin;
16	1	2024-09-29	Завершено	2024-10-01
14	6	2024-09-24	Завершено	2024-10-01
24	8	2024-09-30	Завершено	2024-10-01
23	5	2024-09-30	Завершено	2024-10-01
25	7	2024-09-30	Завершено	2024-10-01
17	2	2024-09-29	Завершено	2024-09-30
19	1	2024-09-29	Завершено	2024-09-30
20	1	2024-09-29	Завершено	2024-09-30
28	5	2024-10-01	Завершено	2024-10-01
27	2	2024-10-01	В процессе	\N
4	4	2024-09-04	Завершено	2024-09-10
6	6	2024-09-06	Завершено	2024-09-12
8	8	2024-09-08	В процессе	\N
7	7	2024-09-07	Завершено	2024-09-08
9	3	2024-09-08	Завершено	2024-09-25
1	1	2024-09-01	Завершено	2024-09-05
18	2	2024-09-29	Завершено	2024-09-29
3	3	2024-09-03	Завершено	2024-09-29
13	1	2024-09-24	Завершено	2024-09-29
2	2	2024-09-02	Завершено	2024-09-29
11	3	2024-09-24	Завершено	2024-09-29
15	1	2024-09-29	Завершено	2024-09-29
5	5	2024-09-05	Завершено	2024-09-29
21	7	2024-09-30	Завершено	2024-09-30
10	\N	2024-09-24	Завершено	2024-09-30
12	3	2024-09-24	Отменен	\N
22	4	2024-09-30	Завершено	2024-09-30
26	7	2024-10-01	Завершено	2024-10-01
\.


--
-- TOC entry 5006 (class 0 OID 27868)
-- Dependencies: 239
-- Data for Name: calls; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.calls (id_call, call_date, number_call, fn_call, way_contact, id_manager) FROM stdin;
1	2024-09-30 17:54:07	+79155573232	Горшков Александр Сергеевич	Звонок	2
2	2024-09-30 20:55:15	+84842231	Данченко Артем Павлович	WhatsApp	2
3	2024-09-30 20:55:23	+844332131	Сергушов Алексей Генадьевич	WhatsApp	1
\.


--
-- TOC entry 4984 (class 0 OID 20140)
-- Dependencies: 217
-- Data for Name: client; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.client (fn_client, number_client, id_client, id_user) FROM stdin;
Иванов Иван Иванович	89001234567	1	1
Петров Петр Петрович	89002345678	2	2
Сидоров Сидор Сидорович	89003456789	3	3
Алексеев Алексей Алексеевич	89004567890	4	4
Морозов Мороз Морозович	89005678901	5	5
Фёдоров Фёдор Фёдорович	89006789012	6	6
Кузнецов Кузьма Кузьмич	89007890123	7	7
Семенов Семен Семенович	89008901234	8	8
Менеджер	12121212	11	9
Работник	123123	12	10
Васильев Максим Дмитриевич	+79802726372	9	19
Болдырев Роман Владимирович	+7859573436211	10	20
Димитриев Владимир Максимович	+79802726372	13	22
Чубаров Алексей Юрьевич	+89876453212	14	23
Латышева Мария Егоровна	+79588654000	15	24
Юлия Гаршина	+7986545232	16	25
\.


--
-- TOC entry 4985 (class 0 OID 20143)
-- Dependencies: 218
-- Data for Name: client_equip; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.client_equip (id_equip, id_client, name_equip, sn_equip, description_equip) FROM stdin;
2	1	ПК HP Pavilion	SN123457	Странные звуки из корпуса
21	2	Ноутбук Hp	123134	Сломался
23	5	Монитор Ardor	677678	Не работает hdmi
24	7	Мышка logitech g102	78678	Даблклик
37	15	душа	1253666	Болит
13	7	ПК Razer	23231	Странные звуки
22	8	Ноутбук MACBOOK	1214234	Не работает клавиша Р
25	8	Мышь ARDOR GAMING	123123	Дабл клик
26	2	Мышь MSI gi11	646445	Открепилась боковая кнопка
27	2	Клавиатура logitech g pro	988112	Не работае капс лок
28	1	Компьютер	-	Сам собрал компьютер, но не запускается
29	1	Монитор ARDOR Infinite	44444	Артефакты на экране
30	1	Компьютер	-	Вирусы
31	1	Нотбук HONOR MAGICBOOK 14	334223	Быстро садится батарея
32	14	Монитор HP	2213	Все плохо
33	2	Рука	-	Сломалась
34	2	Роутер TP-Link 2410	11	Вай фай не работает
35	2	Компьютер ARDOR	1111	Пропала подсветка
36	2	Ноутбук HUAWEI MEDBOOK	11	Быстро греется
38	1	Ноутбук Razer	6666	Низкая яркость экрана
39	16	Ноутбук Huawei Matebook 16	7777	Низкая яркость экрана
1	1	Ноутбук Acer Aspire 5	SN123456	Не работает экран
3	3	Ноутбук Dell Inspiron	SN123458	Сломалась клавиатура
4	4	ПК Lenovo ThinkCentre	SN123459	Сильно греется
5	5	Ноутбук ASUS VivoBook	SN123460	Не работает hdmi 
6	6	ПК Acer Nitro	SN123461	Сгорела видеокарта
7	7	Ноутбук HP Envy	SN123462	Не включается
8	8	ПК Dell XPS	SN123463	Температура 90 градусов на процессоре при просто рабочем столе
\.


--
-- TOC entry 4988 (class 0 OID 20150)
-- Dependencies: 221
-- Data for Name: details; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.details (id_detail, amount_detail, name_detail, price_detail) FROM stdin;
2	12	Жесткий диск 1TB HDD	4000.00
5	4	Процессор Intel i5	10000.00
6	16	Блок питания 500W	2500.00
1	7	Оперативная память 8GB DDR4	3000.00
3	9	Сетевой адаптер Intel	1500.00
9	10	Мышка Razer Dethader	2000
7	10	Корпус для ПК ATX	2000.00
8	3	Материнская плата ASUS	8000.00
10	9	Монитор ACER 24	10000
4	4	Видеокарта NVIDIA GTX 1650	15000.00
\.


--
-- TOC entry 4990 (class 0 OID 20156)
-- Dependencies: 223
-- Data for Name: included; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.included (id_booking, id_equip) FROM stdin;
1	1
9	2
2	3
3	4
4	5
5	6
10	21
11	22
12	23
13	24
14	25
15	26
16	27
17	28
18	29
19	30
20	31
21	32
22	33
23	34
24	35
25	36
26	37
27	38
28	39
\.


--
-- TOC entry 4991 (class 0 OID 20159)
-- Dependencies: 224
-- Data for Name: manager; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.manager (id_manager, fn_managed, number_managed, birth_manager, status_manager) FROM stdin;
1	Иванов Иван Иванович	8900123456	1985-05-10	Работает
2	Петров Петр Петрович	8900234567	1990-04-20	Работает
3	Сидоров Сидор Сидорович	8900345678	1988-02-15	Работает
4	Алексеев Алексей Алексеевич	8900456789	1992-11-25	Работает
5	Морозов Мороз Морозович	8900567890	1987-07-30	Работает
6	Фёдоров Фёдор Фёдорович	8900678901	1980-01-05	Работает
7	Кузнецов Кузьма Кузьмич	8900789012	1995-03-18	Работает
8	Семенов Семен Семенович	8900890123	1989-09-09	Уволен
9	Кирилл Васильевич Пупкин	manager	2003-03-19	Уволен
10	Кирилл Васильевич Пупкин	manager	2003-03-19	Уволен
\.


--
-- TOC entry 4993 (class 0 OID 20163)
-- Dependencies: 226
-- Data for Name: provided_service; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.provided_service (amount_service_provided, id_provided_service, id_service, id_worker, id_booking) FROM stdin;
1	1	6	2	1
1	2	3	2	9
1	3	9	7	18
1	7	3	\N	12
1	8	9	4	12
1	12	9	5	19
1	14	1	\N	20
1	15	4	\N	20
1	16	2	5	19
1	17	3	5	19
1	18	2	\N	3
1	19	4	\N	3
1	20	4	\N	3
1	21	2	\N	13
1	22	4	\N	13
1	23	5	\N	13
1	24	6	\N	13
1	25	3	\N	2
1	30	9	2	11
1	26	1	2	11
1	27	9	5	15
1	28	1	5	15
1	29	3	\N	5
1	31	9	1	21
1	32	3	1	21
1	33	4	1	21
1	34	7	1	21
1	35	9	6	10
1	36	1	6	10
1	37	9	5	16
1	38	9	5	16
1	39	9	5	22
1	40	5	5	22
1	41	9	5	26
1	42	1	5	26
1	43	22	5	26
1	44	17	5	26
1	45	1	5	16
1	46	1	\N	14
1	47	9	5	24
1	48	1	5	24
1	49	9	8	23
1	50	9	4	25
1	51	1	4	25
1	52	9	7	28
1	53	1	7	28
1	54	9	4	27
\.


--
-- TOC entry 5004 (class 0 OID 20421)
-- Dependencies: 237
-- Data for Name: reviews; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.reviews (id_review, id_booking, text_review, rating) FROM stdin;
1	1	Все круто	5
2	9	Все плохо	1
10	2	Все чикипуки	5
11	21	Все было чудесно	5
12	10	Все хорошо	5
13	26	Душа больше не болит	5
3	11	Сойдет	1
4	17	Почти заработало, остались неполадки	4
5	19	Все сломалось спустя день	1
6	18	Менеджер хамил	1
7	20	Не отдали ноутбук	1
8	3	Не заработало	1
9	13	Все круто	5
\.


--
-- TOC entry 4995 (class 0 OID 20167)
-- Dependencies: 228
-- Data for Name: service; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.service (id_service, name_service, price_service, description_service) FROM stdin;
1	Установка ОС Windows	2 000,00 ?	Полная установка операционной системы Windows, включая настройку и обновления.
3	Чистка системы охлаждения	1 200,00 ?	Очистка от пыли и замена термопасты для повышения эффективности охлаждения.
4	Установка программного обеспечения	1 000,00 ?	Установка необходимого программного обеспечения и настройка под ваши нужды.
5	Ремонт материнской платы	5 000,00 ?	Ремонт неисправностей материнской платы, включая замену поврежденных компонентов.
6	Замена видеокарты	4 500,00 ?	Замена устаревшей или поврежденной видеокарты на более современную.
8	Консультация по технике	800,00 ?	Профессиональная консультация по вопросам компьютерной техники и ее использования.
2	Замена комплектующего	1 500,00 ?	Замена неисправного или устаревшего компонента компьютера, такого как жесткий диск или оперативная память.
7	Оптимизация системы	2 500,00 ?	Настройка системы для повышения производительности, включая удаление ненужных программ и файлов.
9	Работа мастера	0,00 ?	Время, затраченное мастером на диагностику и решение проблемы.
10	Ремонт блока питания	3 500,00 ?	Ремонт неисправного блока питания, включая замену компонентов.
11	Установка антивирусного ПО	1 200,00 ?	Установка и настройка антивирусного программного обеспечения для защиты системы.
12	Тестирование системы	900,00 ?	Проверка работоспособности системы и выявление возможных неисправностей.
13	Ремонт клавиатуры	1 500,00 ?	Ремонт или замена клавиш и других компонентов клавиатуры.
14	Настройка интернета	1 100,00 ?	Настройка подключения к интернету, включая диагностику и решение проблем с сетью.
15	Замена матрицы экрана	4 000,00 ?	Замена поврежденной матрицы ноутбука или монитора на новую.
16	Восстановление данных	3 000,00 ?	Восстановление утерянных или поврежденных данных с жестких дисков и других носителей.
17	Настройка Wi-Fi роутера	1 500,00 ?	Настройка и оптимизация работы Wi-Fi роутера для обеспечения стабильного соединения.
18	Замена корпуса компьютера	2 000,00 ?	Замена старого или поврежденного корпуса компьютера на новый.
19	Чистка ноутбука от пыли	1 200,00 ?	Очистка внутренних компонентов ноутбука от пыли для предотвращения перегрева.
20	Установка и настройка ОС Linux	2 500,00 ?	Установка и настройка операционной системы Linux, включая необходимые пакеты и драйвера.
21	Обновление BIOS	1 000,00 ?	Обновление BIOS для улучшения совместимости и производительности системы.
22	Устранение вирусов	1 800,00 ?	Поиск и удаление вирусов и вредоносного ПО с компьютера.
23	Настройка удаленного доступа	1 300,00 ?	Настройка доступа к компьютеру через удаленные соединения для управления и поддержки.
24	Ремонт и настройка принтера	2 800,00 ?	Ремонт принтеров и настройка их работы, включая устранение неполадок.
25	Ремонт мышки	500,00 ?	\N
26	Чистка кулера	2 000,00 ?	\N
\.


--
-- TOC entry 4997 (class 0 OID 20171)
-- Dependencies: 230
-- Data for Name: used_detail; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.used_detail (id_used_detail, id_detail, id_booking, amount_used_detail) FROM stdin;
1	4	1	1
3	3	12	1
4	6	19	1
5	8	20	1
6	1	19	1
7	4	19	1
8	7	3	1
9	6	3	1
10	6	3	1
11	2	13	1
12	3	13	1
13	1	13	1
14	7	13	1
15	8	2	1
16	4	11	1
17	2	15	1
18	5	5	1
19	6	21	1
20	1	21	1
21	3	10	1
22	8	22	1
23	10	26	1
24	4	24	1
\.


--
-- TOC entry 4999 (class 0 OID 20175)
-- Dependencies: 232
-- Data for Name: users; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.users (id_user, username, password, email, role) FROM stdin;
19	666666611112	a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3	asdsad@bk.xn	client
21	ooo	a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3	maxrox@bk.ru	client
23	dog	a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3	dog@mail.ru	client
24	киса	f3cc1585a11891dc513449efeda4b54db0f5328045e530489a1ec4f85a5c1213	123@gmail.ru	client
25	garshina	a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3	garshina@bk.ru	client
1	ivan	a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3	ivanov@example.com	client
9	manager	6ee4a469cd4e91053847f5d3fcb61dbcc91e8f0ef10be7748da4c4a1ba382d17	smart@service.ru	manager
10	worker	87eba76e7f3164534045ba922e7770fb58bbd14ad732bbf5ba6f11cc56989e6e	smart@service.ru	worker
2	petr	a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3	petrov@example.com	client
3	sidor	a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3	sidorov@example.com	client
4	alex	a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3	alekseev@example.com	client
5	moroz	a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3	morozov@example.com	client
6	fedor	a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3	fedorov@example.com	client
7	kuznec	a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3	kuznetsov@example.com	client
8	semen	a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3	semenov@example.com	client
20	aWAW	a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3	max@yazndex.ru	client
22	ffff	a665a45920422f9d417e4867efdc4fb8a04a1f3fff1fa07e998e86f7f7a27ae3	max@bk.ru	client
\.


--
-- TOC entry 5001 (class 0 OID 20182)
-- Dependencies: 234
-- Data for Name: worker; Type: TABLE DATA; Schema: public; Owner: postgres
--

COPY public.worker (id_worker, fn_worker, number_worker, birth_worker, status_worker) FROM stdin;
1	Золотарева Мария Егоровна	89031234567	1985-01-15	Работает
2	Эрхов Дмитрий Владимирович	89032345678	1990-02-20	Работает
4	Ненахов Дмитрий Аркадьевич	89034567890	1992-04-25	Работает
5	Болдырев Максим Романович	89035678901	1980-05-30	Работает
6	Дьяков Дмитрий Владимирович	89036789012	1975-06-18	Работает
7	Данченко Александр Сергеевич	89037890123	1987-07-04	Работает
8	Горшков Александр Эдуардович	89038901234	1993-08-15	Работает
9	Кирилл Васильевич Пупкин	+89876453212	2003-09-19	Уволен
3	Толстунов Владимир Дмитриевич	89033456789	1988-03-10	Уволен
\.


--
-- TOC entry 5025 (class 0 OID 0)
-- Dependencies: 216
-- Name: booking_id_booking_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.booking_id_booking_seq', 28, true);


--
-- TOC entry 5026 (class 0 OID 0)
-- Dependencies: 238
-- Name: calls_id_call_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.calls_id_call_seq', 3, true);


--
-- TOC entry 5027 (class 0 OID 0)
-- Dependencies: 219
-- Name: client_equip_id_equip_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.client_equip_id_equip_seq', 39, true);


--
-- TOC entry 5028 (class 0 OID 0)
-- Dependencies: 220
-- Name: client_id_client_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.client_id_client_seq', 16, true);


--
-- TOC entry 5029 (class 0 OID 0)
-- Dependencies: 222
-- Name: details_id_detai_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.details_id_detai_seq', 10, true);


--
-- TOC entry 5030 (class 0 OID 0)
-- Dependencies: 225
-- Name: manager_id_manager_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.manager_id_manager_seq', 10, true);


--
-- TOC entry 5031 (class 0 OID 0)
-- Dependencies: 227
-- Name: provided_service_od_provoded_service_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.provided_service_od_provoded_service_seq', 54, true);


--
-- TOC entry 5032 (class 0 OID 0)
-- Dependencies: 236
-- Name: reviews_id_review_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.reviews_id_review_seq', 13, true);


--
-- TOC entry 5033 (class 0 OID 0)
-- Dependencies: 229
-- Name: service_id_service_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.service_id_service_seq', 26, true);


--
-- TOC entry 5034 (class 0 OID 0)
-- Dependencies: 231
-- Name: used_detail_id_used_detail_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.used_detail_id_used_detail_seq', 24, true);


--
-- TOC entry 5035 (class 0 OID 0)
-- Dependencies: 233
-- Name: user_id_user_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.user_id_user_seq', 25, true);


--
-- TOC entry 5036 (class 0 OID 0)
-- Dependencies: 235
-- Name: worker_id_worker_seq; Type: SEQUENCE SET; Schema: public; Owner: postgres
--

SELECT pg_catalog.setval('public.worker_id_worker_seq', 9, true);


--
-- TOC entry 4822 (class 2606 OID 27873)
-- Name: calls calls_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.calls
    ADD CONSTRAINT calls_pkey PRIMARY KEY (id_call);


--
-- TOC entry 4777 (class 2606 OID 20197)
-- Name: booking pk_booking; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.booking
    ADD CONSTRAINT pk_booking PRIMARY KEY (id_booking);


--
-- TOC entry 4782 (class 2606 OID 20199)
-- Name: client pk_client; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.client
    ADD CONSTRAINT pk_client PRIMARY KEY (id_client);


--
-- TOC entry 4787 (class 2606 OID 20201)
-- Name: client_equip pk_client_equip; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.client_equip
    ADD CONSTRAINT pk_client_equip PRIMARY KEY (id_equip);


--
-- TOC entry 4790 (class 2606 OID 20203)
-- Name: details pk_details; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.details
    ADD CONSTRAINT pk_details PRIMARY KEY (id_detail);


--
-- TOC entry 4795 (class 2606 OID 20205)
-- Name: included pk_included; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.included
    ADD CONSTRAINT pk_included PRIMARY KEY (id_booking, id_equip);


--
-- TOC entry 4798 (class 2606 OID 20207)
-- Name: manager pk_manager; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.manager
    ADD CONSTRAINT pk_manager PRIMARY KEY (id_manager);


--
-- TOC entry 4801 (class 2606 OID 20209)
-- Name: provided_service pk_provided_service; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.provided_service
    ADD CONSTRAINT pk_provided_service PRIMARY KEY (id_provided_service);


--
-- TOC entry 4806 (class 2606 OID 20211)
-- Name: service pk_service; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.service
    ADD CONSTRAINT pk_service PRIMARY KEY (id_service);


--
-- TOC entry 4811 (class 2606 OID 20213)
-- Name: used_detail pk_used_detail; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.used_detail
    ADD CONSTRAINT pk_used_detail PRIMARY KEY (id_used_detail);


--
-- TOC entry 4814 (class 2606 OID 20215)
-- Name: users pk_user; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.users
    ADD CONSTRAINT pk_user PRIMARY KEY (id_user);


--
-- TOC entry 4817 (class 2606 OID 20217)
-- Name: worker pk_worker; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.worker
    ADD CONSTRAINT pk_worker PRIMARY KEY (id_worker);


--
-- TOC entry 4820 (class 2606 OID 20428)
-- Name: reviews reviews_pkey; Type: CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.reviews
    ADD CONSTRAINT reviews_pkey PRIMARY KEY (id_review);


--
-- TOC entry 4774 (class 1259 OID 20218)
-- Name: booking_pk; Type: INDEX; Schema: public; Owner: postgres
--

CREATE UNIQUE INDEX booking_pk ON public.booking USING btree (id_booking);


--
-- TOC entry 4783 (class 1259 OID 20219)
-- Name: client_equip_pk; Type: INDEX; Schema: public; Owner: postgres
--

CREATE UNIQUE INDEX client_equip_pk ON public.client_equip USING btree (id_equip);


--
-- TOC entry 4779 (class 1259 OID 20220)
-- Name: client_pk; Type: INDEX; Schema: public; Owner: postgres
--

CREATE UNIQUE INDEX client_pk ON public.client USING btree (id_client);


--
-- TOC entry 4784 (class 1259 OID 20221)
-- Name: cliets_equip; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX cliets_equip ON public.client_equip USING btree (id_equip, id_client, name_equip, sn_equip);


--
-- TOC entry 4808 (class 1259 OID 20222)
-- Name: consists_of_fk; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX consists_of_fk ON public.used_detail USING btree (id_detail);


--
-- TOC entry 4775 (class 1259 OID 20223)
-- Name: date_requ; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX date_requ ON public.booking USING btree (date_request);


--
-- TOC entry 4788 (class 1259 OID 20224)
-- Name: details_pk; Type: INDEX; Schema: public; Owner: postgres
--

CREATE UNIQUE INDEX details_pk ON public.details USING btree (id_detail);


--
-- TOC entry 4780 (class 1259 OID 20225)
-- Name: for_auth2_fk; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX for_auth2_fk ON public.client USING btree (id_user);


--
-- TOC entry 4785 (class 1259 OID 20226)
-- Name: has_fk; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX has_fk ON public.client_equip USING btree (id_client);


--
-- TOC entry 4791 (class 1259 OID 20227)
-- Name: included2_fk; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX included2_fk ON public.included USING btree (id_equip);


--
-- TOC entry 4792 (class 1259 OID 20228)
-- Name: included_fk; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX included_fk ON public.included USING btree (id_booking);


--
-- TOC entry 4799 (class 1259 OID 20229)
-- Name: included_in_fk; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX included_in_fk ON public.provided_service USING btree (id_booking);


--
-- TOC entry 4793 (class 1259 OID 20230)
-- Name: included_pk; Type: INDEX; Schema: public; Owner: postgres
--

CREATE UNIQUE INDEX included_pk ON public.included USING btree (id_booking, id_equip);


--
-- TOC entry 4809 (class 1259 OID 20231)
-- Name: is_in_fk; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX is_in_fk ON public.used_detail USING btree (id_booking);


--
-- TOC entry 4796 (class 1259 OID 20233)
-- Name: manager_pk; Type: INDEX; Schema: public; Owner: postgres
--

CREATE UNIQUE INDEX manager_pk ON public.manager USING btree (id_manager);


--
-- TOC entry 4778 (class 1259 OID 20234)
-- Name: places_fk; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX places_fk ON public.booking USING btree (id_manager);


--
-- TOC entry 4802 (class 1259 OID 20235)
-- Name: provide_fk; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX provide_fk ON public.provided_service USING btree (id_worker);


--
-- TOC entry 4803 (class 1259 OID 20236)
-- Name: provide_from_fk; Type: INDEX; Schema: public; Owner: postgres
--

CREATE INDEX provide_from_fk ON public.provided_service USING btree (id_service);


--
-- TOC entry 4804 (class 1259 OID 20237)
-- Name: provided_service_pk; Type: INDEX; Schema: public; Owner: postgres
--

CREATE UNIQUE INDEX provided_service_pk ON public.provided_service USING btree (id_provided_service);


--
-- TOC entry 4807 (class 1259 OID 20238)
-- Name: service_pk; Type: INDEX; Schema: public; Owner: postgres
--

CREATE UNIQUE INDEX service_pk ON public.service USING btree (id_service);


--
-- TOC entry 4812 (class 1259 OID 20239)
-- Name: used_detail_pk; Type: INDEX; Schema: public; Owner: postgres
--

CREATE UNIQUE INDEX used_detail_pk ON public.used_detail USING btree (id_used_detail);


--
-- TOC entry 4815 (class 1259 OID 20240)
-- Name: user_pk; Type: INDEX; Schema: public; Owner: postgres
--

CREATE UNIQUE INDEX user_pk ON public.users USING btree (id_user);


--
-- TOC entry 4818 (class 1259 OID 20241)
-- Name: worker_pk; Type: INDEX; Schema: public; Owner: postgres
--

CREATE UNIQUE INDEX worker_pk ON public.worker USING btree (id_worker);


--
-- TOC entry 4835 (class 2620 OID 20242)
-- Name: client delete_client_trigger; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER delete_client_trigger BEFORE DELETE ON public.client FOR EACH ROW EXECUTE FUNCTION public.delete_client();


--
-- TOC entry 4836 (class 2620 OID 20243)
-- Name: used_detail update_stock_details_trigger; Type: TRIGGER; Schema: public; Owner: postgres
--

CREATE TRIGGER update_stock_details_trigger AFTER INSERT ON public.used_detail FOR EACH ROW EXECUTE FUNCTION public.update_stock_details();


--
-- TOC entry 4834 (class 2606 OID 27874)
-- Name: calls calls_id_manager_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.calls
    ADD CONSTRAINT calls_id_manager_fkey FOREIGN KEY (id_manager) REFERENCES public.manager(id_manager);


--
-- TOC entry 4823 (class 2606 OID 20249)
-- Name: booking fk_booking_places_manager; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.booking
    ADD CONSTRAINT fk_booking_places_manager FOREIGN KEY (id_manager) REFERENCES public.manager(id_manager) ON UPDATE RESTRICT ON DELETE RESTRICT;


--
-- TOC entry 4825 (class 2606 OID 20254)
-- Name: client_equip fk_client_e_has_client; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.client_equip
    ADD CONSTRAINT fk_client_e_has_client FOREIGN KEY (id_client) REFERENCES public.client(id_client) ON UPDATE RESTRICT ON DELETE RESTRICT;


--
-- TOC entry 4824 (class 2606 OID 20259)
-- Name: client fk_client_for_auth2_user; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.client
    ADD CONSTRAINT fk_client_for_auth2_user FOREIGN KEY (id_user) REFERENCES public.users(id_user) ON UPDATE RESTRICT ON DELETE RESTRICT;


--
-- TOC entry 4826 (class 2606 OID 20264)
-- Name: included fk_included_included2_client_e; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.included
    ADD CONSTRAINT fk_included_included2_client_e FOREIGN KEY (id_equip) REFERENCES public.client_equip(id_equip) ON UPDATE RESTRICT ON DELETE RESTRICT;


--
-- TOC entry 4827 (class 2606 OID 20269)
-- Name: included fk_included_included_booking; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.included
    ADD CONSTRAINT fk_included_included_booking FOREIGN KEY (id_booking) REFERENCES public.booking(id_booking) ON UPDATE RESTRICT ON DELETE RESTRICT;


--
-- TOC entry 4828 (class 2606 OID 20274)
-- Name: provided_service fk_provided_included__booking; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.provided_service
    ADD CONSTRAINT fk_provided_included__booking FOREIGN KEY (id_booking) REFERENCES public.booking(id_booking) ON UPDATE RESTRICT ON DELETE RESTRICT;


--
-- TOC entry 4829 (class 2606 OID 20279)
-- Name: provided_service fk_provided_provide_f_service; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.provided_service
    ADD CONSTRAINT fk_provided_provide_f_service FOREIGN KEY (id_service) REFERENCES public.service(id_service) ON UPDATE RESTRICT ON DELETE RESTRICT;


--
-- TOC entry 4830 (class 2606 OID 20284)
-- Name: provided_service fk_provided_provide_worker; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.provided_service
    ADD CONSTRAINT fk_provided_provide_worker FOREIGN KEY (id_worker) REFERENCES public.worker(id_worker) ON UPDATE RESTRICT ON DELETE RESTRICT;


--
-- TOC entry 4831 (class 2606 OID 20289)
-- Name: used_detail fk_used_det_consists__details; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.used_detail
    ADD CONSTRAINT fk_used_det_consists__details FOREIGN KEY (id_detail) REFERENCES public.details(id_detail) ON UPDATE RESTRICT ON DELETE RESTRICT;


--
-- TOC entry 4832 (class 2606 OID 20294)
-- Name: used_detail fk_used_det_is_in_booking; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.used_detail
    ADD CONSTRAINT fk_used_det_is_in_booking FOREIGN KEY (id_booking) REFERENCES public.booking(id_booking) ON UPDATE RESTRICT ON DELETE RESTRICT;


--
-- TOC entry 4833 (class 2606 OID 20429)
-- Name: reviews reviews_id_booking_fkey; Type: FK CONSTRAINT; Schema: public; Owner: postgres
--

ALTER TABLE ONLY public.reviews
    ADD CONSTRAINT reviews_id_booking_fkey FOREIGN KEY (id_booking) REFERENCES public.booking(id_booking);


--
-- TOC entry 5012 (class 0 OID 0)
-- Dependencies: 5
-- Name: SCHEMA public; Type: ACL; Schema: -; Owner: postgres
--

REVOKE USAGE ON SCHEMA public FROM PUBLIC;


-- Completed on 2024-10-28 10:49:15

--
-- PostgreSQL database dump complete
--

